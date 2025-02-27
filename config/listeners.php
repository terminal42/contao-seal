<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Terminal42\ContaoSeal\EventListener\DataContainer\ContentListener;
use Terminal42\ContaoSeal\EventListener\DataContainer\SearchIndexConfigListener;
use Terminal42\ContaoSeal\FrontendSearch;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->autoconfigure();

    $services->set(ContentListener::class)
        ->args([
            service(FrontendSearch::class),
        ])
    ;

    $services->set(SearchIndexConfigListener::class)
        ->args([
            service(FrontendSearch::class),
            service('translator'),
            service('contao.twig.finder_factory'),
        ])
    ;
};
