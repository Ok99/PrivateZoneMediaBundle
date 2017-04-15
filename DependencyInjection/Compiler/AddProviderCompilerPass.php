<?php

namespace Ok99\PrivateZoneCore\MediaBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddProviderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('sonata.media.provider.image');

        $definition->addMethodCall('addFormat', array('cms', array(
            'format'        => 'jpg',
            'quality'       => 95,
            'width'         => 148,
            'height'        => 83,
        )));
    }
}
