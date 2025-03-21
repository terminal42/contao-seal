<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\ContaoSeal\FrontendSearch;

#[AsContentElement('search')]
class SearchController extends AbstractContentElementController
{
    public function __construct(private readonly FrontendSearch $frontendSearch)
    {
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['monolog.logger.contao.error'] = '?'.LoggerInterface::class;

        return $services;
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $configId = (string) $model->search_index ?? '';

        if ('' === $configId) {
            return new Response();
        }

        try {
            $template->providerTemplate = $this->frontendSearch->getProviderTemplateName($configId, $request);
            $template->providerData = $this->frontendSearch->getProviderTemplateData($configId, $request);
        } catch (\Exception $e) {
            $this->container->get('monolog.logger.contao.error')?->error(\sprintf(
                'Could not render front end search for Content Element ID %s: %s',
                $model->id ?? 0,
                $e->getMessage(),
            ));

            return new Response();
        }

        return $template->getResponse();
    }
}
