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

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->scalarNode('itemsPage')->defaultValue(25)->end()
                ->arrayNode('locales')->defaultValue(array('%locale%'))->prototype('scalar')->end()->end()
                ->arrayNode('bundles')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('itemsPage')->end()
                            ->scalarNode('repository')->isRequired()->end()
                            ->scalarNode('entity')->isRequired()->end()
                            ->arrayNode('listDisplay')->defaultValue(array())->prototype('scalar')->end()->end()
                            ->arrayNode('listLinks')->prototype('scalar')->end()->end()
                            ->arrayNode('listSort')->prototype('scalar')->end()->end()
                            ->arrayNode('listSearch')->prototype('scalar')->end()->end()
                            ->arrayNode('listActions')->prototype('scalar')->end()->end()
                            ->arrayNode('formDisplay')->defaultValue(array())->prototype('scalar')->end()->end()
                            ->scalarNode('listTemplate')->end()
                            ->scalarNode('newTemplate')->end()
                            ->scalarNode('newForm')->end()
                            ->scalarNode('editTemplate')->end()
                            ->scalarNode('editForm')->end()
                            ->scalarNode('listController')->end()
                            ->scalarNode('newController')->end()
                            ->scalarNode('createController')->end()
                            ->scalarNode('editController')->end()
                            ->scalarNode('updateController')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
