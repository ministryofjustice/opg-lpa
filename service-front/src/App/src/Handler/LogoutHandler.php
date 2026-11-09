<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\OneLoginSignOut;
use App\Service\OneLogin\RedirectUriBuilder;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\Logging\Metrics;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly OneLoginSessionManager $oneLoginSessionManager,
        private readonly OneLoginSignOut $oneLoginSignOut,
        private readonly RedirectUriBuilder $redirectUriBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $idToken = null;

        if ($session instanceof SessionInterface) {
            if ($session->has(IdentityTokenRefreshMiddleware::SESSION_KEY_IDENTITY)) {
                Metrics::userLoggedOut($this->logger);
            }

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
        $mockRedirectUri = ($this->redirectUriBuilder)($request->getUri());

        return new RedirectResponse($this->oneLoginSignOut->url($idToken, $logoutUrl, $mockRedirectUri) ?? $logoutUrl);
    }
}
