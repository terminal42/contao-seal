<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Terminal42\ContaoSeal\Provider\ProviderFactoryInterface;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class StandardProviderFactory implements ProviderFactoryInterface
{
    public static function getName(): string
    {
        return 'standard';
    }

    public function createProvider(array $providerConfig): ProviderInterface
    {
        return new StandardProvider($providerConfig);
    }
}
