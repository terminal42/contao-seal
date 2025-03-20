<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Psr\Container\ContainerInterface;
use Terminal42\ContaoSeal\Provider\ProviderFactoryInterface;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

abstract class AbstractProviderFactory implements ProviderFactoryInterface
{
    protected ContainerInterface|null $container = null;

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function createProvider(array $providerConfig): ProviderInterface
    {
        $provider = $this->doCreateProvider($providerConfig);

        if ($provider instanceof AbstractProvider) {
            $provider->setContainer($this->container);
        }

        return $provider;
    }

    abstract public function doCreateProvider(array $providerConfig): ProviderInterface;
}
