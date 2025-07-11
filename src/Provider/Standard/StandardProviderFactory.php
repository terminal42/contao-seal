<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Terminal42\ContaoSeal\Provider\AbstractProviderFactory;
use Terminal42\ContaoSeal\Provider\GeneralProviderConfig;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class StandardProviderFactory extends AbstractProviderFactory
{
    public static function getName(): string
    {
        return 'standard';
    }

    public function doCreateProvider(array $providerConfig, GeneralProviderConfig $generalProviderConfig): ProviderInterface
    {
        return new StandardProvider($providerConfig, $generalProviderConfig);
    }
}
