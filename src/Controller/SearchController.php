<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\ContaoSeal\FrontendSearch;

#[AsContentElement('search')]
class SearchController extends AbstractContentElementController
{
    public function __construct(private readonly FrontendSearch $frontendSearch)
    {
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
            // TODO: log?
            dd($e);

            return new Response();
        }

        return $template->getResponse();
    }
}
