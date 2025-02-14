<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\ContaoManager;

use CmsIg\Seal\Integration\Symfony\SealBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\ContaoSeal\Terminal42ContaoSealBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(SealBundle::class),
            BundleConfig::create(Terminal42ContaoSealBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
