<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Terminal42\ContaoSeal\FrontendSearch;

class ContentListener
{
    public function __construct(private FrontendSearch $frontendSearch)
    {
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback('tl_content', 'fields.search_index.options')]
    public function getIndexOptions(): array
    {
        $options = [];

        foreach ($this->frontendSearch->getEngineConfigs() as $config) {
            $options[$config->getId()] = $config->getName();
        }

        return $options;
    }
}
