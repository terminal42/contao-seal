<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ResponseModifyingProviderInterface
{
    public function modifyResponse(Request $request, Response $response): void;
}
