<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

interface ProviderFactoryInterface
{
    public static function getName(): string;

    /**
     * @param array<mixed> $providerConfig
     */
    public function createProvider(array $providerConfig): ProviderInterface;
}
