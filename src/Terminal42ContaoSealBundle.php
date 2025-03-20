<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terminal42\ContaoSeal\DependencyInjection\CompilerPass\AbstractProviderFactoryPass;

class Terminal42ContaoSealBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AbstractProviderFactoryPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
