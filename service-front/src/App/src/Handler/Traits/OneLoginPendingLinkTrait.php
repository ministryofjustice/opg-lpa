<?php

declare(strict_types=1);

namespace App\Handler\Traits;

use Mezzio\Session\SessionInterface;

trait OneLoginPendingLinkTrait
{
    private const string SESSION_KEY_PENDING_LINK = 'onelogin_pending_link';

    private function pendingLinkSub(SessionInterface $session): ?string
    {
        /** @var mixed $pendingLink */
        $pendingLink = $session->get(self::SESSION_KEY_PENDING_LINK);

        $sub = is_array($pendingLink) ? ($pendingLink['sub'] ?? null) : null;

        return is_string($sub) && $sub !== '' ? $sub : null;
    }
}
