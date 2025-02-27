<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\DependencyInjection;

use CmsIg\Seal\Adapter\AdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\ContaoSeal\FrontendSearch;
use Terminal42\ContaoSeal\Provider\ProviderFactoryInterface;

class Terminal42ContaoSealExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.php');
        $loader->load('listeners.php');

        $adapters = [];

        foreach ($config['adapters'] as $name => $adapterDsn) {
            $adapterServiceId = 'terminal42_contao_seal.adapter.'.$name;

            $container->register($adapterServiceId, AdapterInterface::class)
                ->setFactory([new Reference('cmsig_seal.adapter_factory'), 'createAdapter'])
                ->setArguments([$adapterDsn])
            ;

            $adapters[$name] = new Reference($adapterServiceId);
        }

        $container->getDefinition(FrontendSearch::class)
            ->setArgument('$configs', $config['configs'])
            ->setArgument('$adapters', $adapters)
        ;

        $container->registerForAutoconfiguration(ProviderFactoryInterface::class)
            ->addTag('terminal42_contao_seal.provider_factory')
        ;
    }
}
