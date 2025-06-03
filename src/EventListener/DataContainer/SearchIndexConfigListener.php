<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DC_Table;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ContaoSeal\FrontendSearch;

class SearchIndexConfigListener
{
    public function __construct(
        private readonly FrontendSearch $frontendSearch,
        private readonly TranslatorInterface $translator,
        private readonly FinderFactory $finderFactory,
        private readonly Security $security,
        private readonly ImageSizes $imageSizes,
    ) {
    }

    /**
     * @return array<string, array<string>>
     */
    #[AsCallback(table: 'tl_search_index_config', target: 'fields.imgSize.options')]
    public function __invoke(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return [];
        }

        return $this->imageSizes->getOptionsForUser($user);
    }

    /**
     * @return array<string, string>
     */
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

    /**
     * @return array<string, string>
     */
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

    /**
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_search_index_config', target: 'fields.template.options')]
    public function getTemplateOptions(DC_Table $dc): array
    {
        return $this->finderFactory
            ->create()
            ->identifier('frontend_search/provider/'.($dc->getActiveRecord()['providerFactory'] ?? 'standard'))
            ->withVariants()
            ->asTemplateOptions()
        ;
    }
}
