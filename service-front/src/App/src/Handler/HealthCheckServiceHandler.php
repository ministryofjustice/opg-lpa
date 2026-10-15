<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\System\StatusService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthCheckServiceHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly StatusService $statusService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = [
            'api' => $this->statusService->checkApi(),
            'sessionSaveHandler' => $this->statusService->checkSession(),
            'dynamo' => $this->statusService->checkDynamo(),
        ];

        $ok = true;
        foreach ($response as $value) {
            if (!$value['ok']) {
                $ok = false;
                break;
            }
        }

        $response['ok'] = $ok;
        return new JsonResponse($response, $ok ? 200 : 500);
    }
}
