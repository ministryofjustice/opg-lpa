<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\OneLoginSignOut;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly OneLoginSessionManager $oneLoginSessionManager,
        private readonly OneLoginSignOut $oneLoginSignOut,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $idToken = null;

        if ($session instanceof SessionInterface) {
            $idToken = $this->oneLoginSessionManager->getIdToken($session)
                ?? $this->oneLoginSessionManager->getPendingLink($session)?->idToken;

            $session->clear();
            $session->regenerate();
        }

        $logoutUrl = $this->config['redirects']['logout'] ?? null;

        if ($logoutUrl === null) {
            return new RedirectResponse('/');
        }

        // Also end the user's GOV.UK One Login session; One Login then sends them on to $logoutUrl.
        return new RedirectResponse($this->oneLoginSignOut->url($idToken, $logoutUrl) ?? $logoutUrl);
    }
}
