<?php

declare(strict_types=1);

namespace App\Model;

use MakeShared\DataModel\User\User;

/**
 * Per-request mutable holder for the authenticated user's details.
 *
 * Populated by UserDetailsMiddleware after fetching from the API;
 * consumed by LegacyCompatExtension for navigation rendering.
 *
 * Registered as a shared container singleton so the same instance is injected
 * into both the middleware and the Twig extension.
 */
class UserDetailsHolder
{
    private ?User $user = null;

    public function set(User $user): void
    {
        $this->user = $user;
    }

    public function get(): ?User
    {
        return $this->user;
    }
}
