<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Search\Document;
use Contao\Image\PictureConfiguration;
use Contao\Pagination;
use Loupe\ContextCropper\ContextCropper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractProvider
{
    protected ContainerInterface|null $container = null;

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function getDocumentId(Document $document): string
    {
        return (string) $document->getUri();
    }

    protected function documentMatchesUrlRegex(Document $document, string $regex): bool
    {
        if ('' === $regex) {
            return true;
        }

        $url = (string) $document->getUri();

        if ($url && !preg_match($regex, $url)) {
            return false;
        }

        return true;
    }

    protected function documentMatchesCanonicalRegex(Document $document, string $regex): bool
    {
        if ('' === $regex) {
            return true;
        }

        $canonical = (string) $document->extractCanonicalUri();

        if ($canonical && !preg_match($regex, $canonical)) {
            return false;
        }

        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getService(string $name): object
    {
        if (null === $this->container) {
            throw new \LogicException(\sprintf('Requested service "%s" in your provider but did not provide this service.', $name));
        }

        return $this->container->get($name);
    }

    protected function getPagination(int $total, int $perPage, string $perPageParam): string
    {
        // TODO: convert to a more flexible, nicer solution once Contao migrates to a new Pagination logic
        return (new Pagination($total, $perPage, 7, $perPageParam))->generate("\n  ");
    }

    /**
     * @param array{contentUrl: string, ...} $schemaOrgImageData
     */
    protected function createFigureBuilderFromUrl(array $schemaOrgImageData, PictureConfiguration|array|int|string|null $imageSize): FigureBuilder|null
    {
        /** @var Studio $studio */
        $studio = $this->getService('contao.image.studio');
        /** @var RequestStack $requestStack */
        $requestStack = $this->getService('request_stack');
        /** @var ContaoContext $filesContext */
        $filesContext = $this->getService('contao.assets.files_context');

        $figureBuilder = $studio->createFigureBuilder()->fromUrl($schemaOrgImageData['contentUrl'], [
            $requestStack->getCurrentRequest()->getBaseUrl(),
            $filesContext->getStaticUrl(),
        ]);

        $figureMeta = new Metadata(array_filter([
            Metadata::VALUE_CAPTION => $schemaOrgImageData['caption'] ?? null,
            Metadata::VALUE_TITLE => $schemaOrgImageData['name'] ?? null,
            Metadata::VALUE_ALT => $schemaOrgImageData['alternateName'] ?? null,
        ]));

        return $figureBuilder->setSize($imageSize)->setMetadata($figureMeta);
    }

    protected static function createFormattedContext(array $document, int $numberOfContextChars = 50): string
    {
        if (!isset($document['_formatted'])) {
            return '';
        }

        $context = [];

        foreach ($document['_formatted'] as $value) {
            $context[] = $value;
        }

        $context = implode(' ', $context);

        return (new ContextCropper($numberOfContextChars))->apply($context);
    }
}
