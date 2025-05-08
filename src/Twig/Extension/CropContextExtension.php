<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Twig\Extension;

use Loupe\Matcher\Formatting\Cropper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CropContextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('crop_context', [$this, 'cropContext']),
        ];
    }

    public function cropContext(string $text, int $cropLength = 50, string $highlightTag = 'em', string $cropMarker = '…'): string
    {
        $cropper = new Cropper(
            $cropLength,
            $cropMarker,
            \sprintf('<%s>', $highlightTag),
            \sprintf('</%s>', $highlightTag),
        );

        return $cropper->cropHighlightedText($text);
    }
}
