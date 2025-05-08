<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\ContaoSeal\DependencyInjection\Terminal42ContaoSealExtension;
use Terminal42\ContaoSeal\Provider\AbstractProviderFactory;

class AbstractProviderFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds(Terminal42ContaoSealExtension::PROVIDER_FACTORY_TAG);
        $locateableServices = [
            'contao.image.studio' => new Reference('contao.image.studio'),
            'contao.assets.files_context' => new Reference('contao.assets.files_context'),
            'request_stack' => new Reference('request_stack'),
        ];

        foreach (array_keys($taggedServices) as $id) {
            $definition = $container->findDefinition($id);

            if (!is_a($definition->getClass(), AbstractProviderFactory::class, true)) {
                continue;
            }

            $definition->addMethodCall('setContainer', [ServiceLocatorTagPass::register($container, $locateableServices)]);
        }
    }
}
