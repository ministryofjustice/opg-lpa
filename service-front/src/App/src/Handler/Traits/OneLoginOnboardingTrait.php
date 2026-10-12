<?php

declare(strict_types=1);

namespace App\Handler\Traits;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Shared by the One Login onboarding pages.
 *
 * @property LoggerInterface $logger
 */
trait OneLoginOnboardingTrait
{
    private function getSession(ServerRequestInterface $request): SessionInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('Session middleware is not configured');
        }

        return $session;
    }

    private function signInAgain(): RedirectResponse
    {
        $this->logger->warning('auth.onelogin.onboarding_missing_pending_link');

        return new RedirectResponse('/login');
    }
}
