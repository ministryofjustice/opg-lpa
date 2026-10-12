<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\System\StatusService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthCheckDependenciesHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly StatusService $statusService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'mail' => $this->statusService->checkMail(),
            'ordnanceSurvey' => $this->statusService->checkOrdnanceSurvey(),
        ]);
    }
}
