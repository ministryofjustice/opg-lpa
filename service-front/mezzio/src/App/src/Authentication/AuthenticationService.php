<?php

declare(strict_types=1);

namespace App\Authentication;

use App\Authentication\Adapter\AdapterInterface;
use App\Storage\MezzioSessionStorage;
use App\Model\Service\Authentication\Identity\User;
use Laminas\Authentication\Result;

/**
 * Mezzio port of Application\Model\Service\Authentication\AuthenticationService.
 *
 * Replaces LaminasAuthenticationService (which depends on laminas/laminas-session)
 * with direct MezzioSessionStorage-backed identity management.
 */
class AuthenticationService
{
    private ?MezzioSessionStorage $storage = null;

    public function __construct(private readonly AdapterInterface $adapter)
    {
    }

    public function setStorage(MezzioSessionStorage $storage): void
    {
        $this->storage = $storage;
    }

    // -------------------------------------------------------------------------
    // Credential proxies
    // -------------------------------------------------------------------------

    public function setEmail(#[\SensitiveParameter] string $email): static
    {
        $this->adapter->setEmail($email);
        return $this;
    }

    public function setPassword(#[\SensitiveParameter] string $password): static
    {
        $this->adapter->setPassword($password);
        return $this;
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    /**
     * Authenticate and, on success, persist the identity to the Mezzio session.
     */
    public function authenticate(): Result
    {
        $result = $this->adapter->authenticate();

        if ($result->isValid() && $this->storage !== null) {
            $this->storage->write($result->getIdentity());
        }

        return $result;
    }

    /**
     * Verify credentials. On success, persists the refreshed identity (with
     * the new token returned by the API) to storage so subsequent API calls
     * use the up-to-date token. Returns true if valid, false otherwise.
     */
    public function verify(): bool
    {
        $result = $this->adapter->authenticate();

        if ($result->isValid() && $this->storage !== null) {
            $this->storage->write($result->getIdentity());
        }

        return $result->isValid();
    }

    // -------------------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------------------

    public function getIdentity(): ?User
    {
        if ($this->storage === null) {
            return null;
        }

        return $this->storage->read();
    }

    public function hasIdentity(): bool
    {
        return $this->getIdentity() !== null;
    }

    public function clearIdentity(): void
    {
        $this->storage?->clear();
    }

    // -------------------------------------------------------------------------
    // Session expiry (proxied to the adapter)
    // -------------------------------------------------------------------------

    private function getToken(): ?string
    {
        $identity = $this->getIdentity();
        if ($identity === null) {
            return null;
        }

        $token = $identity->token();
        return $token ?: null;
    }

    /**
     * Get the seconds until the session expires.
     *
     * @return int|null null if the session is not active/timed out
     */
    public function getSessionExpiry(): ?int
    {
        $token = $this->getToken();
        if ($token === null) {
            return null;
        }

        /** @psalm-suppress UndefinedInterfaceMethod */
        $result = $this->adapter->getSessionExpiry($token);

        if ($result === null || !isset($result['valid']) || !$result['valid']) {
            return null;
        }

        return $result['remainingSeconds'];
    }

    /**
     * Set the seconds until the session expires.
     *
     * @return int|null null if the session is not active/timed out
     */
    public function setSessionExpiry(int $expiresInSeconds): ?int
    {
        $token = $this->getToken();
        if ($token === null) {
            return null;
        }

        /** @psalm-suppress UndefinedInterfaceMethod */
        $result = $this->adapter->setSessionExpiry($token, $expiresInSeconds);

        if ($result === null || !isset($result['valid']) || !$result['valid']) {
            return null;
        }

        return $result['remainingSeconds'];
    }
}
