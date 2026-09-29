<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Decides whether a user leaving Make should also be sent to GOV.UK One Login's /logout endpoint.
 *
 * @see https://docs.sign-in.service.gov.uk/integrate-with-integration-environment/managing-your-users-sessions/
 */
class OneLoginSignOut
{
    public function __construct(
        private readonly bool $oneLoginEnabled,
        private readonly OneLoginService $oneLoginService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns the One Login /logout URL for a user who signed in through One Login, or null when
     * One Login is off.
     */
    public function url(#[\SensitiveParameter] ?string $idToken, string $postLogoutRedirectUri): ?string
    {
        if (!$this->oneLoginEnabled || $idToken === null) {
            return null;
        }

        try {
            return $this->oneLoginService->logoutUrl($idToken, $postLogoutRedirectUri);
        } catch (RuntimeException | ClientExceptionInterface $e) {
            $this->logger->warning('auth.onelogin.logout_url_failed', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
