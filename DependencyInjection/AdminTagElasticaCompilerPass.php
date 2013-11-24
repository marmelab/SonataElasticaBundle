<?php

namespace Marmelab\SonataElasticaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

class AdminTagElasticaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('sonata.admin');

        foreach ($taggedServices as $id => $attributes) {

            $attributes = current($attributes);

            if (!isset($attributes['searcher']) || !isset($attributes['search_index']) || $attributes['searcher'] !== 'elastica') {
                continue;
            }

            // get orm services
            $ormGuesser = $this->getSonataORMGuesserService($container, $attributes['manager_type']);
            $ormModelManager = $this->getSonataORMModelManagerService($container, $attributes['manager_type']);
            $finder = $this->getFinderService($container, $attributes['search_index']);

            // create repository, datagrid & modelManager services
            $proxyRepository = $this->createProxyRepositoryService($id, $finder);
            $datagrid = $this->createDatagridService($container, $id, $ormGuesser, $proxyRepository);
            $modelManager = $this->createModelManagerService($id, $ormModelManager, $proxyRepository);

            // define model manager & datagrid to admin service
            $adminService = $container->getDefinition($id);
            $adminService->addMethodCall('setModelManager', array($modelManager));
            $adminService->addMethodCall('setDatagridBuilder', array($datagrid));

            // set custom transformer
            if (isset($attributes['transformer_class']) && !empty($attributes['transformer_class'])) {
                $this->useCustomTransformer($id, $adminService, $finder, $attributes['transformer_class']);
            }
        }
    }

    /**
     * @param string     $adminName
     * @param Definition $finder
     * @return Definition
     */
    private function createProxyRepositoryService($adminName, Definition $finder)
    {
        $serviceName = sprintf('sonata.%s.proxy_repository', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Repository\ElasticaProxyRepository');
        $service->setArguments( array(
            $finder
        ));

        return $service;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $adminName
     * @param Definition       $guesser
     * @param Definition       $proxyRepository
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
     * @return Definition
     */
    private function createModelManagerService($adminName, Definition $ormModelManager, Definition $proxyRepository) {
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
     * @param string     $adminName
     * @param Definition $adminService
     * @param Definition $finderService
     * @param string     $transformerServiceClass
     */
    private function useCustomTransformer($adminName, Definition $adminService,Definition $finderService, $transformerServiceClass) {
        $serviceName = sprintf('sonata.%s.elastica-transformer', $adminName);
        $transformerService = new Definition($serviceName);
        $transformerService->setClass($transformerServiceClass);

        $finderArgs = $finderService->getArguments();
        $finderArgs[1] = $transformerService;
        $finderService->setArguments($finderArgs);

        // set transformer object class
        $transformerService->addMethodCall('setObjectClass', array($adminService->getArgument(1)));
    }

    /**
     * @param ContainerBuilder $container
     * @param $baseManagerType
     * @return Definition
     */
    private function getSonataORMGuesserService(ContainerBuilder $container, $baseManagerType)
    {
        return $container->getDefinition(sprintf('sonata.admin.guesser.%s_datagrid_chain', $baseManagerType));
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $baseManagerType
     * @return Definition
     */
    private function getSonataORMModelManagerService(ContainerBuilder $container, $baseManagerType)
    {
        return $container->getDefinition(sprintf('sonata.admin.manager.%s', $baseManagerType));
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $searchIndex
     * @return Definition
     */
    private function getFinderService(ContainerBuilder $container, $searchIndex)
    {
        return $container->getDefinition(sprintf('fos_elastica.finder.%s', $searchIndex));
    }
}
