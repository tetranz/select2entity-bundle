<?php

namespace Tetranz\Select2EntityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config/packages files
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
        $treeBuilder = new TreeBuilder('tetranz_select2_entity');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
                ->children()
                    ->integerNode('minimum_input_length')->min(0)->defaultValue(1)->end()
                    ->booleanNode('scroll')->defaultFalse()->end()
                    ->integerNode('page_limit')->defaultValue(10)->end()
                    ->booleanNode('allow_clear')->defaultFalse()->end()
                    ->arrayNode('allow_add')->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')->defaultFalse()->end()
                            ->scalarNode('new_tag_text')->defaultValue(' (NEW)')->end()
                            ->scalarNode('new_tag_prefix')->defaultValue('__')->end()
                            ->scalarNode('tag_separators')->defaultValue('[",", " "]')->end()
                        ->end()
                    ->end()
                    ->integerNode('delay')->defaultValue(250)->min(0)->end()
                    ->scalarNode('language')->defaultValue('en')->end()
                    ->scalarNode('theme')->defaultValue('default')->end()
                    ->booleanNode('cache')->defaultTrue()->end()
                    ->integerNode('cache_timeout')->defaultValue(60000)->min(0)->end()
                    ->scalarNode('width')->defaultNull()->end()
                    ->scalarNode('object_manager')->defaultNull()->end()
                    ->booleanNode('render_html')->defaultFalse()->end()
                ->end();

        return $treeBuilder;
    }
}
