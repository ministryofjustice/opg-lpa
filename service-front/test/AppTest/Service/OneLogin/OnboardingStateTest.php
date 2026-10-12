<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\OnboardingState;
use MakeShared\OneLogin\UserType;
use PHPUnit\Framework\TestCase;

final class OnboardingStateTest extends TestCase
{
    public function testStartsWithNoAnswers(): void
    {
        $this->assertNull((new OnboardingState())->userType);
    }

    public function testRoundTripsThroughAnArray(): void
    {
        $state = OnboardingState::fromArray((new OnboardingState(UserType::Professional))->toArray());

        $this->assertSame(UserType::Professional, $state->userType);
    }

    public function testIgnoresAnUnknownUserType(): void
    {
        $this->assertNull(OnboardingState::fromArray(['userType' => 'admin'])->userType);
    }

    public function testIgnoresANonStringUserType(): void
    {
        $this->assertNull(OnboardingState::fromArray(['userType' => ['lay']])->userType);
    }
}
