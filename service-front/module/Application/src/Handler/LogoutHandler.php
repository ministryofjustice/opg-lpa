<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly SessionManagerSupport $sessionManagerSupport,
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->clearSession();

        $logoutUrl = $this->config['redirects']['logout'] ?? '/';

        return new RedirectResponse($logoutUrl);
    }

    private function clearSession(): void
    {
        $this->authenticationService->clearIdentity();

        $this->sessionManagerSupport->getSessionManager()->destroy([
            'clear_storage' => true
        ]);
    }
}
