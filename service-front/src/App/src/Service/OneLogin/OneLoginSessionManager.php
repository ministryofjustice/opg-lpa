<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Middleware\IdentityTokenRefreshMiddleware;
use Mezzio\Session\SessionInterface;

class OneLoginSessionManager
{
    private const string SESSION_KEY_PENDING_LINK = 'onelogin_pending_link';

    /**
     * Held for the whole signed-in session: it is the id_token_hint One Login needs to sign the
     * user out, and its presence is what marks the session as a One Login sign-in.
     */
    private const string SESSION_KEY_ID_TOKEN = 'onelogin_id_token';

    public function setPendingLink(
        SessionInterface $session,
        string $sub,
        string $email,
        #[\SensitiveParameter] string $idToken,
    ): void {
        $session->set(self::SESSION_KEY_PENDING_LINK, [
            'sub'     => $sub,
            'email'   => $email,
            'idToken' => $idToken,
        ]);
    }

    public function getPendingLink(SessionInterface $session): ?PendingLink
    {
        /** @var mixed $pendingLink */
        $pendingLink = $session->get(self::SESSION_KEY_PENDING_LINK);

        if (! is_array($pendingLink)) {
            return null;
        }

        $sub     = $pendingLink['sub'] ?? null;
        $email   = $pendingLink['email'] ?? null;
        $idToken = $pendingLink['idToken'] ?? null;

        if (! is_string($sub) || $sub === '' || ! is_string($idToken) || $idToken === '') {
            return null;
        }

        return new PendingLink($sub, is_string($email) ? $email : '', $idToken);
    }

    public function clearPendingLink(SessionInterface $session): void
    {
        $session->unset(self::SESSION_KEY_PENDING_LINK);
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
