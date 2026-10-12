<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

use MakeShared\OneLogin\UserType;

/**
 * The answers a user gives during One Login onboarding. They are held in the session
 * until the last onboarding page saves them; each new page adds its answer here.
 */
final class OnboardingState
{
    public function __construct(
        public ?UserType $userType = null,
        public ?bool $hasExistingAccount = null,
        public ?bool $keepExistingAccount = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $userType = $data['userType'] ?? null;

        $hasExistingAccount  = $data['hasExistingAccount'] ?? null;
        $keepExistingAccount = $data['keepExistingAccount'] ?? null;

        return new self(
            userType: is_string($userType) ? UserType::tryFrom($userType) : null,
            hasExistingAccount: is_bool($hasExistingAccount) ? $hasExistingAccount : null,
            keepExistingAccount: is_bool($keepExistingAccount) ? $keepExistingAccount : null,
        );
    }

    /**
     * @return array{userType: ?string, hasExistingAccount: ?bool, keepExistingAccount: ?bool}
     */
    public function toArray(): array
    {
        return [
            'userType'            => $this->userType?->value,
            'hasExistingAccount'  => $this->hasExistingAccount,
            'keepExistingAccount' => $this->keepExistingAccount,
        ];
    }

    /**
     * Whether the user is new to Make, or has chosen not to keep their existing account.
     */
    public function createsNewAccount(): bool
    {
        return $this->hasExistingAccount === false || $this->keepExistingAccount === false;
    }

    /**
     * Whether the user has an existing Make account and wants to keep it.
     */
    public function linksExistingAccount(): bool
    {
        return $this->hasExistingAccount === true && $this->keepExistingAccount === true;
    }
}
