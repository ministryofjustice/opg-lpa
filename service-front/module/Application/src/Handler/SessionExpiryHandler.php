<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionExpiryHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManagerSupport $sessionManagerSupport,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $remainingSeconds = $this->authenticationService->getSessionExpiry();

        if (!$remainingSeconds) {
            $this->clearSession();

            return new EmptyResponse(204);
        }

        return new JsonResponse(['remainingSeconds' => $remainingSeconds]);
    }

    private function clearSession(): void
    {
        $this->authenticationService->clearIdentity();

        $this->sessionManagerSupport->getSessionManager()->destroy([
            'clear_storage' => true
        ]);
    }
}
