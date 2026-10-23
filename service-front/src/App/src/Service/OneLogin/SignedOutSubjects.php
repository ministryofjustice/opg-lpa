<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use App\Service\Redis\RedisClient;
use RuntimeException;

/**
 * Remembers when One Login users signed out of One Login, as told by its back-channel
 * logout notification.
 *
 * A user who has signed in to One Login but not yet linked or created a Make account
 * only has a pending link in their session; there is no Make session to end. Comparing
 * the sign-out time with when that pending link's sign-in happened stops the journey
 * until they sign in to One Login again, without blocking a newer sign-in.
 *
 */
class SignedOutSubjects
{
    private const string KEY_PREFIX = 'onelogin_signed_out:';

    private bool $connected = false;

    /**
     * @param RedisClient $redis its own client, not the one the session handler opens and closes
     */
    public function __construct(
        private readonly RedisClient $redis,
    ) {
    }

    /**
     * @param int $signedOutAt the logout token's `iat`, as a Unix timestamp
     */
    public function recordSignOut(string $sub, int $signedOutAt): void
    {
        $latest = max($signedOutAt, $this->lastSignOut($sub) ?? 0);

        if (!$this->redis->write($this->key($sub), (string) $latest)) {
            throw new RuntimeException('Failed to record One Login sign-out');
        }
    }

    /**
     * True if the user signed out of One Login at or after the sign-in that started
     * their pending link, so that pending link must not be used.
     */
    public function signedOutSince(string $sub, int $signedInAt): bool
    {
        $signedOutAt = $this->lastSignOut($sub);

        return $signedOutAt !== null && $signedOutAt >= $signedInAt;
    }

    private function lastSignOut(string $sub): ?int
    {
        $this->connect();

        $value = $this->redis->read($this->key($sub));

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
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

    private function key(string $sub): string
    {
        return self::KEY_PREFIX . hash('sha256', $sub);
    }
}
