<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use Psr\Http\Message\UriInterface;

final class RedirectUriBuilder
{
    private const CALLBACK_PATH = '/auth/redirect';

    public function __construct(private readonly ?string $baseUrl = null)
    {
    }

    public function __invoke(UriInterface $requestUri, string $path = self::CALLBACK_PATH): string
    {
        $base = $this->baseUrl
            ?? ($requestUri->getScheme() . '://' . $requestUri->getAuthority());

        return rtrim($base, '/') . $path;
    }
}
