<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteLpaHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $queryParams = $request->getQueryParams();

        $page = $queryParams['page'] ?? null;
        $lpaId = $routeMatch?->getParam('lpa-id');

        if ($this->lpaApplicationService->deleteApplication($lpaId) !== true) {
            $this->flashMessenger->addErrorMessage('LPA could not be deleted');
        }

        if (is_numeric($page)) {
            return new RedirectResponse(sprintf('/user/dashboard/page/%s', $page));
        }

        return new RedirectResponse('/user/dashboard');
    }
}
