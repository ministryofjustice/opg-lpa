<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use Mezzio\Session\SessionInterface;

class OneLoginSessionManager
{
    private const string SESSION_KEY_PENDING_LINK = 'onelogin_pending_link';

    public function setPendingLink(SessionInterface $session, string $sub, string $email): void
    {
        $session->set(self::SESSION_KEY_PENDING_LINK, [
            'sub'   => $sub,
            'email' => $email,
        ]);
    }

    public function getPendingLink(SessionInterface $session): ?PendingLink
    {
        /** @var mixed $pendingLink */
        $pendingLink = $session->get(self::SESSION_KEY_PENDING_LINK);

        if (! is_array($pendingLink)) {
            return null;
        }

        $sub   = $pendingLink['sub'] ?? null;
        $email = $pendingLink['email'] ?? null;

        if (! is_string($sub) || $sub === '') {
            return null;
        }

        return new PendingLink($sub, is_string($email) ? $email : '');
    }

    public function clearPendingLink(SessionInterface $session): void
    {
        $session->unset(self::SESSION_KEY_PENDING_LINK);
    }
}
