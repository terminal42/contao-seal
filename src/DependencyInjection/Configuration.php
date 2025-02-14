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
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')->end()
                        ->end()
                    ->end()
                    ->defaultValue($this->getLoupeDefaultAdapterIfSupported())
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
            'loupe_default' => 'loupe://%kernel.project_dir%/var/frontend_search',
        ];
    }
}
