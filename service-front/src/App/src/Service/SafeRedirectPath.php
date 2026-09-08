<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\Diactoros\Uri;
use Laminas\Diactoros\Exception\ExceptionInterface;

/**
 * Constrains a redirect target to a path on this site.
 *
 * We remember where a signed-out user was heading and send them back there once they
 * have signed in.
 */
final class SafeRedirectPath
{
    /**
     * @return non-empty-string|null the target if it is a path on this site, otherwise null
     */
    public static function filter(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            $uri = new Uri($value);
        } catch (ExceptionInterface) {
            return null;
        }

        if ($uri->getScheme() !== '' || $uri->getAuthority() !== '') {
            return null;
        }

        $path = $uri->getPath();

        if (!str_starts_with($path, '/')) {
            return null;
        }

        $query = $uri->getQuery();

        return $query === '' ? $path : $path . '?' . $query;
    }
}
