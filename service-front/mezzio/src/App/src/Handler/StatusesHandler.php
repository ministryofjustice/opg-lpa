<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
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
        $lpaIds = $request->getAttribute('lpa-ids');

        $statuses = $this->lpaApplicationService->getStatuses($lpaIds);

        return new JsonResponse($statuses);
    }
}
