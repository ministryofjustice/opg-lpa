<?php

declare(strict_types=1);

namespace App\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionExpiryHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $remainingSeconds = $this->authenticationService->getSessionExpiry();

        if ($remainingSeconds === null || $remainingSeconds <= 0) {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

            if ($session instanceof SessionInterface) {
                $session->clear();
                $session->regenerate();
            }

            return new EmptyResponse(204);
        }

        return new JsonResponse(['remainingSeconds' => $remainingSeconds]);
    }
}
