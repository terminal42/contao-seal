<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Psr\Container\ContainerInterface;

abstract class AbstractProviderFactory implements ProviderFactoryInterface
{
    protected ContainerInterface|null $container = null;

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function createProvider(array $providerConfig): ProviderInterface
    {
        $defaultProviderConfig = $this->createGeneralProviderConfig($providerConfig);
        $provider = $this->doCreateProvider($providerConfig, $defaultProviderConfig);

        if ($provider instanceof AbstractProvider) {
            $provider->setContainer($this->container);
        }

        return $provider;
    }

    /**
     * @param array<mixed> $providerConfig
     */
    protected function createGeneralProviderConfig(array $providerConfig): GeneralProviderConfig
    {
        if (isset($providerConfig['pageParameter'])) {
            $pageParameter = $providerConfig['pageParameter'];
        } elseif (isset($providerConfig['id'])) {
            $pageParameter = 'page_s_'.$providerConfig['id'];
        } else {
            $pageParameter = GeneralProviderConfig::DEFAULT_PAGE_PARAMETER;
        }

        return (new GeneralProviderConfig($this->getTemplate($providerConfig)))
            ->withPerPage($providerConfig['perPage'] ?? GeneralProviderConfig::DEFAULT_PER_PAGE)
            ->withQueryParameter($providerConfig['queryParameter'] ?? GeneralProviderConfig::DEFAULT_QUERY_PARAMETER)
            ->withHighlightTag($providerConfig['highlightTag'] ?? GeneralProviderConfig::DEFAULT_HIGHLIGHT_TAG)
            ->withPageParameter($pageParameter)
            ->withUrlRegex(Util::buildRegexFromListWizard($providerConfig['urls'] ?? ''))
            ->withCanonicalRegex(Util::buildRegexFromListWizard($providerConfig['canonicals'] ?? ''))
            ->withImageSize($providerConfig['imgSize'] ?? null)
            ->withImageSize($providerConfig['imgSize'] ?? null)
        ;
    }

    /**
     * @param array<mixed> $providerConfig
     */
    protected function getTemplate(array $providerConfig): string
    {
        return $providerConfig['template'] ?? \sprintf('@Contao/frontend_search/%s.html.twig', static::getName());
    }

    /**
     * @param array<mixed> $providerConfig
     */
    abstract protected function doCreateProvider(array $providerConfig, GeneralProviderConfig $defaultProviderConfig): ProviderInterface;
}
