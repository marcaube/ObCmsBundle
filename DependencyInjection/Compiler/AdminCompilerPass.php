<?php

namespace Ob\CmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AdminCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('ob.cms.admin_container')) {
            return;
        }

        $definition = $container->getDefinition('ob.cms.admin_container');

        foreach ($container->findTaggedServiceIds('ob.cms.admin') as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall('addClass', array(new Reference($id), $attributes["alias"]));
            }
        }
    }
}