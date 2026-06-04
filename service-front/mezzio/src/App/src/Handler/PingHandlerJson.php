<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\System\StatusService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PingHandlerJson implements RequestHandlerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly StatusService $statusService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->statusService->check();
        $result['tag'] = $this->config['version']['tag'] ?? '';
        return new JsonResponse($result);
    }
}
