<?php

namespace Marmelab\SonataElasticaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AdminTagElasticaCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
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
            $finderService = $this->createFinderService($container, $attributes['search_index']);

            // create repository & modelManager services
            $mapping = (isset($attributes['fields_mapping'])) ? $container->getParameter($attributes['fields_mapping']) : array();
            $proxyRepositoryService = $this->createProxyRepositoryService($container->getParameter('marmelab.elastica.proxy_repository_class'), $id, $finderService, $mapping);
            $modelManagerService = $this->createModelManagerService($id, $ormModelManagerService, $proxyRepositoryService);

            // create datagrid builder service
            $searchForm = isset($attributes['search_form']) ? new Reference($attributes['search_form']) : null;
            $datagridService = $this->createDatagridService($container, $id, $ormGuesserService, $proxyRepositoryService, $searchForm);

            // define model manager & datagrid to admin service
            $adminService = $container->getDefinition($id);
            $adminService->addMethodCall('setModelManager', array($modelManagerService));
            $adminService->addMethodCall('setDatagridBuilder', array($datagridService));

            // set custom transformer
            if (isset($attributes['fastGrid']) && $attributes['fastGrid']) {
                if (isset($attributes['transformer'])) {
                    $transformerService = $this->getCustomTransformer($container, $attributes['transformer']);
                } else {
                    $transformerService = $this->createBasicTransformer($id);
                }
                $this->useTransformer($transformerService, $adminService, $finderService);
            }
        }
    }

    /**
     * @param string           $class
     * @param string           $adminName
     * @param Definition       $finder
     *
     * @return Definition
     */
    private function createProxyRepositoryService($class, $adminName, Definition $finder, array $mapping)
    {
        $serviceName = sprintf('sonata.%s.proxy_repository', $adminName);
        $service = new Definition($serviceName);
        $service->setClass($class);
        $service->setArguments(array(
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
     * @param Reference        $searchForm
     *
     * @return Definition
     */
    private function createDatagridService(ContainerBuilder $container, $adminName, Definition $guesser, Definition $proxyRepository, Reference $searchForm = null)
    {
        $serviceName = sprintf('sonata.%s.datagrid', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Builder\ElasticaDatagridBuilder');
        $service->setArguments(array(
            $container->getDefinition('form.factory'),
            $container->getDefinition('sonata.admin.builder.filter.factory'),
            $guesser,
            $proxyRepository,
            $searchForm
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
    private function createFinderService(ContainerBuilder $container, $searchIndex)
    {
        $defaultFinderServiceName = sprintf('fos_elastica.finder.%s', $searchIndex);
        $defaultFinderService = $container->getDefinition($defaultFinderServiceName);

        $finderService = new Definition($defaultFinderServiceName.'.admin');
        $finderService->setClass($defaultFinderService->getClass());
        $finderService->setArguments($defaultFinderService->getArguments());

        return $finderService;
    }
}
