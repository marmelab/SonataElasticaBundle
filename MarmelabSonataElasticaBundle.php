<?php

namespace Marmelab\SonataElasticaBundle;

use Marmelab\SonataElasticaBundle\DependencyInjection\AdminTagElasticaCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarmelabSonataElasticaBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new AdminTagElasticaCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
