<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouteResult;
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
        $routeResult = $request->getAttribute(RouteResult::class);
        $lpaIds = $routeResult?->getMatchedParams()['lpa-ids'] ?? '';

        $statuses = $this->lpaApplicationService->getStatuses($lpaIds);

        // Ensure every requested ID appears in the response so the JS can always
        // do results[id] without hitting undefined. The API may omit IDs that have
        // no processing status yet (e.g. newly-created LPAs).
        $requestedIds = array_filter(explode(',', $lpaIds));
        foreach ($requestedIds as $id) {
            if (!isset($statuses[$id])) {
                $statuses[$id] = ['found' => false];
            }
        }

        return new JsonResponse($statuses);
    }
}
