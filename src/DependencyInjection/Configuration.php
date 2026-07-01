<?php

declare(strict_types=1);

namespace UniRateApi\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('unirate');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('UniRate API key (get one at https://unirateapi.com)')
                ->end()
                ->scalarNode('base_url')
                    ->defaultValue('https://api.unirateapi.com')
                    ->info('UniRate API base URL (override for testing)')
                ->end()
                ->integerNode('timeout')
                    ->defaultValue(30)
                    ->info('HTTP request timeout in seconds')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
