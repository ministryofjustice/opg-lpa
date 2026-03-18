<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionKeepAliveHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly SessionManager $sessionManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(['refreshed' => $this->sessionManager->sessionExists()]);
    }
}
