<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Terminal42\ContaoSeal\FrontendSearch;

#[AsEventListener]
class ResponseListener
{
    public function __construct(
        private ScopeMatcher $scopeMatcher,
        private FrontendSearch $frontendSearch,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if ($response->isRedirection() || !$response->isSuccessful()) {
            return;
        }

        if (!$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        $this->frontendSearch->modifyResponse($request, $response);
    }
}
