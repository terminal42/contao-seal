<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Terminal42\ContaoSeal\Provider\ProviderInterface;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProviderFactory extends AbstractProviderFactory
{
    public static function getName(): string
    {
        return 'standard';
    }

    public function doCreateProvider(array $providerConfig): ProviderInterface
    {
        //   dd($providerConfig);
        return new StandardProvider(
            Util::buildRegexFromListWizard($providerConfig['urls'] ?? ''),
            Util::buildRegexFromListWizard($providerConfig['canonicals'] ?? ''),
            $providerConfig['imgSize'] ?? null,
        );
    }
}
