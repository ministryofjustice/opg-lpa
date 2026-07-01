<?php

declare(strict_types=1);

namespace App\Service\ApiClient\Response;

class AuthResponse
{
    private ?string $userId = null;
    private ?string $username = null;
    private ?string $token = null;
    private ?int $expiresIn = null;
    private ?string $expiresAt = null;
    private ?string $lastLogin = null;
    private ?bool $inactivityFlagsCleared = null;
    private ?string $errorDescription = null;

    public function __construct(array $array = [])
    {
        $this->userId                 = $array['userId'] ?? null;
        $this->token                  = $array['token'] ?? null;
        $this->lastLogin              = $array['last_login'] ?? null;
        $this->username               = $array['username'] ?? null;
        $this->expiresIn              = isset($array['expiresIn']) ? (int) $array['expiresIn'] : null;
        $this->expiresAt              = $array['expiresAt'] ?? null;
        $this->inactivityFlagsCleared = $array['inactivityFlagsCleared'] ?? null;
    }

    public static function buildFromResponse(array $result): self
    {
        return new self($result);
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getInactivityFlagsCleared(): ?bool
    {
        return $this->inactivityFlagsCleared;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    public function setErrorDescription(string $errorDescription): static
    {
        $this->errorDescription = $errorDescription;
        return $this;
    }

    public function isAuthenticated(): bool
    {
        return !empty($this->userId) && !empty($this->token) && empty($this->errorDescription);
    }
}
