<?php

namespace Marmelab\SonataElasticaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
            $ormGuesser = $this->getSonataORMGuesserService($attributes['manager_type']);
            $ormModelManager = $this->getSonataORMModelManagerService($attributes['manager_type']);

            // create repository, datagrid & modelManager services
            $proxyRepository = $this->createProxyRepositoryService($id);
            $datagrid = $this->createDatagridService($container, $id, $ormGuesser, $proxyRepository);
            $modelManager = $this->createModelManagerService($id, $ormModelManager, $proxyRepository);


            // define model manager & datagrid to admin service
            $adminService = $container->getDefinition($id);
            $adminService->addMethodCall('setModelManager', array($modelManager));
            $adminService->addMethodCall('setDatagridBuilder', array($datagrid));

            $defaultAdminFinderId = 'fos_elastica.finder.' .$attributes['search_index'];

            if (!isset($attributes['transformer'])) {
                $this->useDefaultTransformer($adminService, $defaultAdminFinderId);
            } else if (isset($attributes['transformer']) && !empty($attributes['transformer'])) {
                $this->useCustomTransformer($container, $adminService, $defaultAdminFinderId, $attributes['transformer']);
            }
        }
    }

    /**
     * @param string $adminName
     * @return Definition
     */
    private function createProxyRepositoryService($adminName)
    {
        $serviceName = sprintf('sonata.%s.proxy_repository', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Repository\ElasticaProxyRepository');

        return $service;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $adminName
     * @param Reference        $guesser
     * @param Reference        $proxyRepository
     * @return Definition
     */
    private function createDatagridService($container, $adminName, $guesser, $proxyRepository)
    {
        $serviceName = sprintf('sonata.%s.datagrid', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Builder\ElasticaDatagridBuilder');

        $arguments = array();
        $arguments[0] = $container->getDefinition('form.factory');
        $arguments[1] = $container->getDefinition('sonata.admin.builder.filter.factory');
        $arguments[2] = $guesser;
        $arguments[3] = $proxyRepository;

        $service->setArguments($arguments);

        return $service;
    }

    private function createModelManagerService($adminName, $ormModelManager, $proxyRepository) {
        $serviceName = sprintf('sonata.%s.model_manager', $adminName);
        $service = new Definition($serviceName);
        $service->setClass('Marmelab\SonataElasticaBundle\Model\ElasticaModelManager');

        $arguments = array();
        $arguments[0] = $ormModelManager;
        $arguments[1] = $proxyRepository;

        $service->setArguments($arguments);

        return $service;
    }

    private function useDefaultTransformer($adminService, $finderServiceID) {
        $args = $adminService->getArguments();
        $args[3] = new Reference($finderServiceID);
        $adminService->setArguments($args);
    }

    private function useCustomTransformer($container, $adminService, $finderServiceID, $transformerServiceID) {
        $transformerService = new Definition($transformerServiceID);
        $transformerService->setClass('Marmelab\SonataElasticaBundle\Transformer\ElasticaToModelTransformer');


        // get default finder for admin
        $defaultFinderService = $container->getDefinition($finderServiceID);
        $finderArgs = $defaultFinderService->getArguments();

        $finderArgs[1] = $transformerService;
        $defaultFinderService->setArguments($finderArgs);

        // set transformer object class
        $transformerService->addMethodCall('setObjectClass', array($adminService->getArgument(1)));

        // add admin finder service to admin service
        $args = $adminService->getArguments();
        $args[3] = ($defaultFinderService);
        $adminService->setArguments($args);
    }

    /**
     * @param string $baseManagerType
     * @return Reference
     */
    private function getSonataORMGuesserService($baseManagerType)
    {
        return new Reference(sprintf('sonata.admin.guesser.%s_datagrid_chain', $baseManagerType));
    }

    /**
     * @param string $baseManagerType
     * @return Reference
     */
    private function getSonataORMModelManagerService($baseManagerType)
    {
        return new Reference(sprintf('sonata.admin.manager.%s', $baseManagerType));
    }

}
