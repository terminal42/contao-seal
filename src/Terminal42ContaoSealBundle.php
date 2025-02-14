<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42ContaoSealBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
