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
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $userType = $data['userType'] ?? null;

        return new self(
            userType: is_string($userType) ? UserType::tryFrom($userType) : null,
        );
    }

    /**
     * @return array{userType: ?string}
     */
    public function toArray(): array
    {
        return [
            'userType' => $this->userType?->value,
        ];
    }
}
