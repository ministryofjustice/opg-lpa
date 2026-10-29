<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Service\Redis\RedisClient;
use RuntimeException;

/**
 * Rejects a back-channel logout token whose `jti` has been seen in the last 3 minutes, as
 * https://docs.sign-in.service.gov.uk/integrate-with-integration-environment/managing-your-users-sessions/
 * requires.
 */
class LogoutTokenReplayGuard
{
    private const string KEY_PREFIX = 'onelogin_logout_jti:';

    private const int REPLAY_WINDOW_SECONDS = 180;

    private bool $connected = false;

    /**
     * @param RedisClient $redis
     */
    public function __construct(
        private readonly RedisClient $redis,
    ) {
    }

    /**
     * @return bool false if the token's `jti` was already claimed
     * @throws RuntimeException if Redis cannot be reached
     */
    public function claim(#[\SensitiveParameter] string $logoutToken): bool
    {
        $jti = $this->jti($logoutToken);

        if ($jti === null) {
            return true;
        }

        $this->connect();

        return $this->redis->writeIfAbsent($this->key($jti), '1', self::REPLAY_WINDOW_SECONDS);
    }

    public function release(#[\SensitiveParameter] string $logoutToken): void
    {
        $jti = $this->jti($logoutToken);

        if ($jti === null) {
            return;
        }

        $this->connect();

        $this->redis->destroy($this->key($jti));
    }

    private function jti(string $logoutToken): ?string
    {
        $parts = explode('.', $logoutToken);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);

        /** @var mixed $claims */
        $claims = $payload === false ? null : json_decode($payload, true);

        $jti = is_array($claims) ? ($claims['jti'] ?? null) : null;

        return is_string($jti) && $jti !== '' ? $jti : null;
    }

    private function connect(): void
    {
        if ($this->connected) {
            return;
        }

        if (!$this->redis->open()) {
            throw new RuntimeException('Unable to connect to Redis');
        }

        $this->connected = true;
    }

    private function key(string $jti): string
    {
        return self::KEY_PREFIX . hash('sha256', $jti);
    }
}
