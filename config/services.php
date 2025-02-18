<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoSeal\Controller\SearchController;
use Terminal42\ContaoSeal\FrontendSearch;
use Terminal42\ContaoSeal\Indexer\SealDelegatingIndexer;
use Terminal42\ContaoSeal\Provider\Standard\StandardProviderFactory;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(FrontendSearch::class)
        ->args([
            service('database_connection'),
            service('translator'),
            [], // Configs set by extension
            [], // Adapters set by extension
            tagged_iterator('terminal42_contao_seal.provider_factory', null, 'getName'),
        ])
        ->public()
    ;

    $services->set(SearchController::class)
        ->args([
            service(FrontendSearch::class),
        ])
        ->public()
    ;

    $services->set(SealDelegatingIndexer::class)
        ->args([
            service(FrontendSearch::class),
        ])
    ;

    $services->set(StandardProviderFactory::class);
};
