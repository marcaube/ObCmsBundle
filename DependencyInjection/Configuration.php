<?php

namespace Ob\CmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ob_cms');

        $rootNode
            ->children()
                ->arrayNode('locales')->defaultValue(array('%locale%'))->prototype('scalar')->end()->end()
                ->scalarNode('logo')->defaultValue('http://placehold.it/400x65')->end()

                ->arrayNode('classes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('controller')->defaultValue('Ob\CmsBundle\Controller\AdminController')->end()
                    ->end()
                ->end()

                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('layout')->defaultValue('ObCmsBundle::layout.html.twig')->end()
                        ->scalarNode('menu')->defaultValue('ObCmsBundle:Menu:menu.html.twig')->end()
                        ->scalarNode('dashboard')->defaultValue('ObCmsBundle:Admin:dashboard.html.twig')->end()
                        ->scalarNode('list')->defaultValue('ObCmsBundle:CRUD:list.html.twig')->end()
                        ->scalarNode('new')->defaultValue('ObCmsBundle:CRUD:new.html.twig')->end()
                        ->scalarNode('edit')->defaultValue('ObCmsBundle:CRUD:edit.html.twig')->end()
                        ->scalarNode('table')->defaultValue('ObCmsBundle:Table:table.html.twig')->end()
                        ->scalarNode('fields')->defaultValue('MopaBootstrapBundle:Form:fields.html.twig')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
