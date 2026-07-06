<?php

declare(strict_types=1);

namespace App\Storage;

use App\Model\Service\Authentication\Identity\User;
use DateTime;
use Laminas\Authentication\Storage\StorageInterface;
use Mezzio\Session\SessionInterface;

/**
 * Laminas authentication storage backed by the Mezzio session.
 *
 * Serialises/deserialises the User identity to/from the flat 'identity' key
 * already used by LoginHandler, so a single canonical session representation
 * is shared across all Mezzio code.
 *
 * The session is injected per-request by IdentityTokenRefreshMiddleware via
 * setSession(), before any call to read()/write()/clear().
 */
class MezzioSessionStorage implements StorageInterface
{
    private const string SESSION_KEY = 'identity';

    private ?SessionInterface $session = null;

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    public function isEmpty(): bool
    {
        return $this->session === null || !$this->session->has(self::SESSION_KEY);
    }

    public function read(): ?User
    {
        if ($this->session === null || !$this->session->has(self::SESSION_KEY)) {
            return null;
        }

        $data = $this->session->get(self::SESSION_KEY);

        if (!is_array($data) || !isset($data['userId'], $data['token'], $data['tokenExpiresAt'])) {
            return null;
        }

        $tokenExpiresAt = new DateTime($data['tokenExpiresAt']);
        $expiresIn      = max(0, $tokenExpiresAt->getTimestamp() - time());
        $lastLogin      = isset($data['lastLogin']) ? new DateTime($data['lastLogin']) : null;

        return new User($data['userId'], $data['token'], $expiresIn, $lastLogin);
    }

    public function write($contents): void
    {
        if ($this->session === null || !$contents instanceof User) {
            return;
        }

        $tokenExpiresAt = $contents->tokenExpiresAt();

        $this->session->set(self::SESSION_KEY, [
            'userId'         => $contents->id(),
            'token'          => $contents->token(),
            'tokenExpiresAt' => $tokenExpiresAt !== null ? $tokenExpiresAt->format('c') : (new DateTime())->format('c'),
            'lastLogin'      => $contents->lastLogin()?->format('c'),
        ]);
    }

    public function clear(): void
    {
        $this->session?->unset(self::SESSION_KEY);
    }
}
