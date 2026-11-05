<?php

declare(strict_types=1);

namespace App\Service;

use App\Feature;

/**
 * Where a signed-out user is sent to sign in again. The One Login start page when One Login
 * is on, otherwise the password sign-in page.
 */
final class SignInPage
{
    /**
     * @param string|null $state 'timeout' or 'internal-system-error', to explain why they were signed out
     */
    public static function path(?string $state = null): string
    {
        $path = Feature::OneLogin->isEnabled() ? '/home' : '/login';

        return $state === null ? $path : $path . '/' . $state;
    }
}
