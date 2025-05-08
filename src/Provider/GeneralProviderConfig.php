<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Contao\Image\PictureConfiguration;

/**
 * Configuration shared by all providers unless super special.
 * 99% of all providers will have these settings so they can re-use this object.
 * Only if you have a provider that is very special and does not take any keyword parameter and does not allow
 * filtering on URLs or Canonical URLs, you should implement all the logic yourself.
 */
final class GeneralProviderConfig
{
    public const DEFAULT_QUERY_PARAMETER = 'keywords';

    public const DEFAULT_HIGHLIGHT_TAG = 'em';

    public const DEFAULT_PER_PAGE = 10;

    public const DEFAULT_PAGE_PARAMETER = 'page';

    private string $queryParameter = self::DEFAULT_QUERY_PARAMETER;

    private string $pageParameter = self::DEFAULT_PAGE_PARAMETER;

    private string $highlightTag = self::DEFAULT_HIGHLIGHT_TAG;

    private int $perPage = self::DEFAULT_PER_PAGE;

    private string $urlRegex = '';

    private string $canonicalRegex = '';

    /**
     * @var PictureConfiguration|array<mixed>|int|string|null
     */
    private PictureConfiguration|array|int|string|null $imageSize = null;

    public function __construct(private string $templateName)
    {
    }

    public function getHighlightTag(): string
    {
        return $this->highlightTag;
    }

    public function withHighlightTag(string $highlightTag): self
    {
        if ('' === $highlightTag) {
            throw new \InvalidArgumentException('Highlight tag cannot be empty.');
        }

        $clone = clone $this;
        $clone->highlightTag = $highlightTag;

        return $clone;
    }

    public function getPageParameter(): string
    {
        return $this->pageParameter;
    }

    public function withPageParameter(string $pageParameter): self
    {
        if ('' === $pageParameter) {
            throw new \InvalidArgumentException('Page parameter cannot be empty.');
        }
        $clone = clone $this;
        $clone->pageParameter = $pageParameter;

        return $clone;
    }

    public function getQueryParameter(): string
    {
        return $this->queryParameter;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function withQueryParameter(string $queryParameter): self
    {
        if ('' === $queryParameter) {
            throw new \InvalidArgumentException('Query parameter cannot be empty.');
        }

        $clone = clone $this;
        $clone->queryParameter = $queryParameter;

        return $clone;
    }

    public function withPerPage(int $perPage): self
    {
        if ($perPage < 1) {
            throw new \InvalidArgumentException('perPage cannot be less than 1.');
        }

        $clone = clone $this;
        $clone->perPage = $perPage;

        return $clone;
    }

    public function getUrlRegex(): string
    {
        return $this->urlRegex;
    }

    public function getCanonicalRegex(): string
    {
        return $this->canonicalRegex;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * @return PictureConfiguration|array<mixed>|int|string|null
     */
    public function getImageSize(): PictureConfiguration|array|int|string|null
    {
        return $this->imageSize;
    }

    /**
     * @param PictureConfiguration|array<mixed>|int|string|null $imageSize
     */
    public function withImageSize(PictureConfiguration|array|int|string|null $imageSize): self
    {
        $clone = clone $this;
        $clone->imageSize = $imageSize;

        return $clone;
    }

    public function withUrlRegex(string $regex): self
    {
        $clone = clone $this;
        $clone->urlRegex = $regex;

        return $clone;
    }

    public function withCanonicalRegex(string $regex): self
    {
        $clone = clone $this;
        $clone->canonicalRegex = $regex;

        return $clone;
    }
}
