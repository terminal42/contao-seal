<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Exception;

class DocumentIgnoredException extends \RuntimeException
{
    private function __construct(string $message, \Throwable|null $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function because(string $reason, \Throwable|null $previous = null): self
    {
        return new self($reason, $previous);
    }
}
