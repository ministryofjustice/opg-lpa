<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StatusesHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $lpaIds = $routeMatch?->getParam('lpa-ids');

        $statuses = $this->lpaApplicationService->getStatuses($lpaIds);

        return new JsonResponse($statuses);
    }
}
