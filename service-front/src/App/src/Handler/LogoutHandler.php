<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly bool $oneLoginEnabled,
        private readonly OneLoginService $oneLoginService,
        private readonly OneLoginSessionManager $oneLoginSessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $idToken = null;

        if ($session instanceof SessionInterface) {
            $idToken = $this->oneLoginSessionManager->getIdToken($session);

            $session->clear();
            $session->regenerate();
        }

        $logoutUrl = $this->config['redirects']['logout'] ?? '/';

        if ($this->oneLoginEnabled && $idToken !== null) {
            // Also end the user's GOV.UK One Login session; One Login then sends them on to $logoutUrl.
            try {
                return new RedirectResponse($this->oneLoginService->logoutUrl($idToken, $logoutUrl));
            } catch (RuntimeException | ClientExceptionInterface $e) {
                $this->logger->warning('auth.onelogin.logout_url_failed', ['message' => $e->getMessage()]);
            }
        }

        return new RedirectResponse($logoutUrl);
    }
}
