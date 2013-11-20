<?php

namespace Marmelab\SonataElasticaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AdminTagElasticaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('sonata.admin');
        $proxyManagerServiceId = $container->getParameter('proxy_model_manager_service_id');
        $datagridServiceId = $container->getParameter('datagrid_service_id');

        foreach ($taggedServices as $id => $attributes) {

            $attributes = current($attributes);

            if (!isset($attributes['searcher']) || !isset($attributes['search_index']) || $attributes['searcher'] !== 'elastica') {
                continue;
            }

            // define model manager & datagrid
            $adminService = $container->getDefinition($id);
            $adminService->addMethodCall('setModelManager', array(new Reference($proxyManagerServiceId)));
            $adminService->addMethodCall('setDatagridBuilder', array(new Reference($datagridServiceId)));

            // set base admin manager for proxy model manager
            $proxyModelManagerService = $container->getDefinition($proxyManagerServiceId);
            $args = $proxyModelManagerService->getArguments();
            $args[0] = new Reference(sprintf('sonata.admin.manager.%s', $attributes['manager_type']));
            $proxyModelManagerService->setArguments($args);

            // set guesser for datagrid
            $datagridService = $container->getDefinition($datagridServiceId);
            $args = $datagridService->getArguments();
            $args[2] = new Reference(sprintf('sonata.admin.guesser.%s_datagrid_chain', $attributes['manager_type']));
            $datagridService->setArguments($args);

            $defaultAdminFinderId = 'fos_elastica.finder.' .$attributes['search_index'];

            // use default transformer
            if (!isset($attributes['transformer'])) {
                // add admin finder service to admin service
                $args = $adminService->getArguments();
                $args[3] = new Reference($defaultAdminFinderId);
                $adminService->setArguments($args);
            }
            // use custom transformer
            else if (isset($attributes['transformer']) && !empty($attributes['transformer'])) {

                $transformerService = $container->getDefinition($attributes['transformer']);

                // get default finder for admin
                $defaultFinderService = $container->getDefinition($defaultAdminFinderId);
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
        }
    }
}
