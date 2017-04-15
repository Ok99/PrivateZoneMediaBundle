<?php

namespace Ok99\PrivateZoneCore\MediaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Ok99\PrivateZoneCore\MediaBundle\DependencyInjection\Compiler\AddProviderCompilerPass;

class Ok99PrivateZoneMediaBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddProviderCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataMediaBundle';
    }
}
