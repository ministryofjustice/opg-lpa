<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Middleware\AuthenticationMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Service\SafeRedirectPath;
use Mezzio\Session\SessionInterface;
use Psr\Log\LoggerInterface;

class OneLoginSessionManager
{
    private const string SESSION_KEY_PENDING_LINK = 'onelogin_pending_link';

    private const string SESSION_KEY_ONBOARDING = 'onelogin_onboarding';

    public const int PENDING_LINK_MAX_AGE_SECONDS = 3600;

    /**
     * Held for the whole signed-in session: it is the id_token_hint One Login needs to sign the
     * user out, and its presence is what marks the session as a One Login sign-in.
     */
    private const string SESSION_KEY_ID_TOKEN = 'onelogin_id_token';

    public function __construct(
        private readonly SignedOutSubjects $signedOutSubjects,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setPendingLink(
        SessionInterface $session,
        string $sub,
        string $email,
        #[\SensitiveParameter] string $idToken,
        int $signedInAt,
    ): void {
        $session->set(self::SESSION_KEY_PENDING_LINK, [
            'sub'        => $sub,
            'email'      => $email,
            'idToken'    => $idToken,
            'signedInAt' => $signedInAt,
        ]);
    }

    /**
     * The user's pending link, or null if there is none they may still use. One Login says
     * that after a back-channel logout the user must not continue until they sign in again:
     * https://docs.sign-in.service.gov.uk/integrate-with-integration-environment/managing-your-users-sessions/
     */
    public function getPendingLink(SessionInterface $session): ?PendingLink
    {
        /** @var mixed $pendingLink */
        $pendingLink = $session->get(self::SESSION_KEY_PENDING_LINK);

        if (! is_array($pendingLink)) {
            return null;
        }

        $sub        = $pendingLink['sub'] ?? null;
        $email      = $pendingLink['email'] ?? null;
        $idToken    = $pendingLink['idToken'] ?? null;
        $signedInAt = $pendingLink['signedInAt'] ?? null;

        if (! is_string($sub) || $sub === '' || ! is_string($idToken) || $idToken === '' || ! is_int($signedInAt)) {
            return null;
        }

        if (time() - $signedInAt > self::PENDING_LINK_MAX_AGE_SECONDS) {
            return null;
        }

        if ($this->signedOutSubjects->signedOutSince($sub, $signedInAt)) {
            $this->logger->info('auth.onelogin.pending_link_signed_out');
            $this->clearPendingLink($session);

            return null;
        }

        return new PendingLink($sub, is_string($email) ? $email : '', $idToken, $signedInAt);
    }

    public function clearPendingLink(SessionInterface $session): void
    {
        $session->unset(self::SESSION_KEY_PENDING_LINK);
    }

    /**
     * The onboarding answers given so far. Starting the Make session clears them.
     */
    public function getOnboarding(SessionInterface $session): OnboardingState
    {
        /** @var mixed $onboarding */
        $onboarding = $session->get(self::SESSION_KEY_ONBOARDING);

        return is_array($onboarding) ? OnboardingState::fromArray($onboarding) : new OnboardingState();
    }

    public function saveOnboarding(SessionInterface $session, OnboardingState $onboarding): void
    {
        $session->set(self::SESSION_KEY_ONBOARDING, $onboarding->toArray());
    }

    /**
     * Signs the user in to Make after a One Login sign-in, keeping the ID token alongside the identity.
     *
     * @param array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string} $identity
     */
    public function startSession(
        SessionInterface $session,
        array $identity,
        #[\SensitiveParameter] string $idToken,
    ): void {
        // Regenerate to prevent session fixation before writing any identity data.
        $session->regenerate();
        $session->clear();
        $session->set(IdentityTokenRefreshMiddleware::SESSION_KEY_IDENTITY, $identity);
        $session->set(self::SESSION_KEY_ID_TOKEN, $idToken);
    }

    /**
     * Signs in a user who has finished One Login onboarding, and returns where to send them:
     * the page they first asked for, or their dashboard.
     *
     * @param array{userId: string, token: string, tokenExpiresAt: string, lastLogin: string, sharedSpaceId: ?string} $identity
     */
    public function finishOnboarding(
        SessionInterface $session,
        array $identity,
        #[\SensitiveParameter] string $idToken,
        string $linkedOrCreated,
    ): string {
        $preAuthUrl = SafeRedirectPath::filter($session->get(AuthenticationMiddleware::SESSION_KEY_PRE_AUTH_URL));

        $this->startSession($session, $identity, $idToken);

        return $preAuthUrl ?? '/user/dashboard?account=' . $linkedOrCreated;
    }

    public function getIdToken(SessionInterface $session): ?string
    {
        /** @var mixed $idToken */
        $idToken = $session->get(self::SESSION_KEY_ID_TOKEN);

        return is_string($idToken) && $idToken !== '' ? $idToken : null;
    }

    public function forgetIdToken(SessionInterface $session): void
    {
        $session->unset(self::SESSION_KEY_ID_TOKEN);
    }
}
