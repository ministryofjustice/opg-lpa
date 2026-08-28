<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use Facile\JoseVerifier\JWK\JwksProviderInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Rate-limits JWKS refetches.
 */
final class ThrottledJwksProvider implements JwksProviderInterface
{
    public const COOLDOWN_SECONDS = 60;

    private const COOLDOWN_KEY = 'onelogin_jwks_reload_cooldown';

    public function __construct(
        private readonly JwksProviderInterface $inner,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @psalm-return array{keys: list<array<string, mixed>>}
     */
    public function getJwks(): array
    {
        return $this->inner->getJwks();
    }

    public function reload(): JwksProviderInterface
    {
        if ($this->cache->has(self::COOLDOWN_KEY)) {
            return $this;
        }

        $this->cache->set(self::COOLDOWN_KEY, true, self::COOLDOWN_SECONDS);

        return $this->inner->reload();
    }
}
