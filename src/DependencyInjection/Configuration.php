<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\DependencyInjection;

use Loupe\Loupe\LoupeFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('terminal42_contao_seal');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('adapters')
                    ->info('Define the search adapters. They have to me managed using key-value. The key is the internal name, e.g. stored also in the database when configuring indexes in the backend. The value has to be a valid SEAL DSN.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')->end()
                        ->end()
                    ->end()
                    ->defaultValue($this->getLoupeDefaultAdapterIfSupported())
                ->end()
                ->arrayNode('configs')
                    ->info('Allows to define configurations in the configuration file in addition to in the Contao backend/database.')
                    ->useAttributeAsKey('id')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')->isRequired()->end()
                            ->scalarNode('provider')->isRequired()->end()
                            ->arrayNode('providerConfig')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function getLoupeDefaultAdapterIfSupported(): array
    {
        if (!class_exists(LoupeFactory::class)) {
            return [];
        }

        $loupeFactory = new LoupeFactory();

        if (!$loupeFactory->isSupported()) {
            return [];
        }

        return [
            'loupe_default' => 'loupe://%kernel.project_dir%/var/loupe',
        ];
    }
}
