<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ContaoSeal\FrontendSearch;

class SearchIndexConfigListener
{
    public function __construct(
        private FrontendSearch $frontendSearch,
        private TranslatorInterface $translator,
        private FinderFactory $finderFactory,
    ) {
    }

    #[AsCallback('tl_search_index_config', 'fields.adapter.options')]
    public function getConfigAdapterOptions(): array
    {
        $options = [];

        foreach ($this->frontendSearch->getEngineConfigs() as $config) {
            $name = $config->getAdapterName();
            $options[$name] = $this->translator->trans('tl_search_index_config.adapters.'.$name, [], 'contao_tl_search_index_config');
        }

        return $options;
    }

    #[AsCallback('tl_search_index_config', 'fields.providerFactory.options')]
    public function getProviderFactoryOptions(): array
    {
        $options = [];

        foreach ($this->frontendSearch->getEngineConfigs() as $config) {
            $name = $config->getProviderFactoryName();
            $options[$name] = $this->translator->trans('tl_search_index_config.provider_factory.'.$name, [], 'contao_tl_search_index_config');
        }

        return $options;
    }

    #[AsCallback(table: 'tl_search_index_config', target: 'fields.template.options')]
    public function getTemplateOptions(): array
    {
        return $this->finderFactory
            ->create()
            ->identifier('frontend_search') // TODO: add provider name
            ->withVariants()
            ->asTemplateOptions()
        ;
    }
}
