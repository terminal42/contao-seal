<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Terminal42\ContaoSeal\Provider\ProviderInterface;

class StandardProviderFactory extends AbstractProviderFactory
{
    public static function getName(): string
    {
        return 'standard';
    }

    public function doCreateProvider(array $providerConfig): ProviderInterface
    {
        return new StandardProvider($providerConfig);
    }
}
