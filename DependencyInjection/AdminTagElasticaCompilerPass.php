<?php

namespace Marmelab\SonataElasticaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

class AdminTagElasticaCompilerPass implements CompilerPassInterface
{

    const DOCTRINE = 'orm';
    const PHPCR = 'doctrine_phpcr';
    const PROPEL = 'propel';

    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('sonata.admin');

        foreach ($taggedServices as $id => $attributes) {

            $attributes = current($attributes);

            if (!isset($attributes['searcher']) || !isset($attributes['search_index']) || $attributes['searcher'] !== 'elastica') {
                continue;
            }

            // get services
            $ormGuesserService = $this->getSonataORMGuesserService($container, $attributes['manager_type']);
            $ormModelManagerService = $this->getSonataORMModelManagerService($container, $attributes['manager_type']);
            $finderService = $this->getFinderService($container, $attributes['search_index']);

            // create repository, datagrid & modelManager services
            $mapping = (isset($attributes['fields_mapping'])) ? $container->getParameter($attributes['fields_mapping']) : array();
            $proxyRepositoryService = $this->createProxyRepositoryService($id, $finderService, $mapping);
            $datagridService = $this->createDatagridService($container, $id, $ormGuesserService, $proxyRepositoryService);
            $modelManagerService = $this->createModelManagerService($id, $ormModelManagerService, $proxyRepositoryService);

            // define model manager & datagrid to admin service
            $adminService = $container->getDefinition($id);
            $adminService->addMethodCall('setModelManager', array($modelManagerService));
            $adminService->addMethodCall('setDatagridBuilder', array($datagridService));

            // set custom transformer
            if (isset($attributes['fastgrid']) && $attributes['fastgrid']) {
                if (isset($attributes['transformer'])) {
                    $transformerService = $this->getCustomTransformer($container, $attributes['transformer']);
                } else {
                    $transformerService = $this->createBasicTransformer($id);
                }
                $this->useTransformer($transformerService, $adminService, $finderService);
            }

            // override filter class
            $filterServiceName = sprintf('sonata.admin.%s.filter.type.string', $attributes['manager_type']);
            $filterService = $container->getDefinition($filterServiceName);
            switch($attributes['manager_type']) {
                case self::DOCTRINE: $filterService->setClass('Marmelab\SonataElasticaBundle\Filter\DoctrineORMStringFilter'); break;
                case self::PHPCR: $filterService->setClass('Marmelab\SonataElasticaBundle\Filter\DoctrinePHPCRStringFilter'); break;
                case self::PROPEL: $filterService->setClass('Marmelab\SonataElasticaBundle\Filter\PropelStringFilter'); break;
            }
            
        }
    }

    /**
     * @param string     $adminName
     * @param Definition $finder
     *
     * @return Definition
     */
    private function createProxyRepositoryService($adminName, Definition $finder, array $mapping)
    {
        $serviceName = sprintf('sonata.%s.proxy_repository', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Repository\ElasticaProxyRepository');
        $service->setArguments( array(
            $finder,
            $mapping
        ));

        return $service;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $adminName
     * @param Definition       $guesser
     * @param Definition       $proxyRepository
     *
     * @return Definition
     */
    private function createDatagridService(ContainerBuilder $container, $adminName, Definition $guesser, Definition $proxyRepository)
    {
        $serviceName = sprintf('sonata.%s.datagrid', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Builder\ElasticaDatagridBuilder');
        $service->setArguments(array(
            $container->getDefinition('form.factory'),
            $container->getDefinition('sonata.admin.builder.filter.factory'),
            $guesser,
            $proxyRepository
        ));

        return $service;
    }

    /**
     * @param string     $adminName
     * @param Definition $ormModelManager
     * @param Definition $proxyRepository
     *
     * @return Definition
     */
    private function createModelManagerService($adminName, Definition $ormModelManager, Definition $proxyRepository)
    {
        $serviceName = sprintf('sonata.%s.model_manager', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Model\ElasticaModelManager');
        $service->setArguments(array(
            $ormModelManager,
            $proxyRepository
        ));

        return $service;
    }

    /**
     * @param string $adminName
     */
    private function createBasicTransformer($adminName)
    {
        $serviceName = sprintf('sonata.%s.elastica-transformer', $adminName);
        $transformerService = new Definition($serviceName);
        $transformerService->setClass('Marmelab\SonataElasticaBundle\Transformer\ElasticaToModelTransformer');

        return $transformerService;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $transformerServiceName
     *
     * @return Definition
     */
    private function getCustomTransformer(ContainerBuilder $container, $transformerServiceName)
    {
        return $container->getDefinition($transformerServiceName);
    }

    /**
     * @param Definition $transformerService
     * @param Definition $adminService
     * @param Definition $finderService
     */
    private function useTransformer(Definition $transformerService, Definition $adminService, Definition $finderService)
    {
        // tell finder to use custom transformer
        $finderArgs = $finderService->getArguments();
        $finderArgs[1] = $transformerService;
        $finderService->setArguments($finderArgs);

        // set transformer object class
        $transformerService->addMethodCall('setObjectClass', array($adminService->getArgument(1)));
    }

    /**
     * @param ContainerBuilder $container
     * @param $baseManagerType
     *
     * @return Definition
     */
    private function getSonataORMGuesserService(ContainerBuilder $container, $baseManagerType)
    {
        return $container->getDefinition(sprintf('sonata.admin.guesser.%s_datagrid_chain', $baseManagerType));
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $baseManagerType
     *
     * @return Definition
     */
    private function getSonataORMModelManagerService(ContainerBuilder $container, $baseManagerType)
    {
        return $container->getDefinition(sprintf('sonata.admin.manager.%s', $baseManagerType));
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $searchIndex
     *
     * @return Definition
     */
    private function getFinderService(ContainerBuilder $container, $searchIndex)
    {
        return $container->getDefinition(sprintf('fos_elastica.finder.%s', $searchIndex));
    }
}
