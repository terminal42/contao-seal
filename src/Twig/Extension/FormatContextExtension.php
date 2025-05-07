<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Twig\Extension;

use Loupe\Matcher\Formatter;
use Loupe\Matcher\FormatterOptions;
use Loupe\Matcher\Matcher;
use Loupe\Matcher\Tokenizer\Tokenizer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatContextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('seal_format_context', [$this, 'formatContext']),
        ];
    }

    public function formatContext(string $text, string $query, array $options = []): string
    {
        $matcher = new Matcher(new Tokenizer());
        $formatter = new Formatter($matcher);

        return $formatter->format($text, $query, FormatterOptions::fromArray($options))->getFormattedText();
    }
}
