<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\OnboardingState;
use MakeShared\OneLogin\UserType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OnboardingStateTest extends TestCase
{
    public function testStartsWithNoAnswers(): void
    {
        $state = new OnboardingState();

        $this->assertNull($state->userType);
        $this->assertNull($state->hasExistingAccount);
        $this->assertNull($state->keepExistingAccount);
    }

    public function testRoundTripsThroughAnArray(): void
    {
        $state = OnboardingState::fromArray((new OnboardingState(UserType::Professional, true, false))->toArray());

        $this->assertSame(UserType::Professional, $state->userType);
        $this->assertTrue($state->hasExistingAccount);
        $this->assertFalse($state->keepExistingAccount);
    }

    public function testIgnoresANonBooleanExistingAccountAnswer(): void
    {
        $this->assertNull(OnboardingState::fromArray(['hasExistingAccount' => 'yes'])->hasExistingAccount);
    }

    public function testIgnoresAnUnknownUserType(): void
    {
        $this->assertNull(OnboardingState::fromArray(['userType' => 'admin'])->userType);
    }

    public function testIgnoresANonStringUserType(): void
    {
        $this->assertNull(OnboardingState::fromArray(['userType' => ['lay']])->userType);
    }

    /**
     * @return array<string, array{?bool, ?bool, bool, bool}>
     */
    public static function accountChoiceProvider(): array
    {
        return [
            'nothing answered'           => [null, null, false, false],
            'new to Make'                => [false, null, true, false],
            'has an account, not asked'  => [true, null, false, false],
            'keeps their account'        => [true, true, false, true],
            'starts again with a new one' => [true, false, true, false],
        ];
    }

    #[DataProvider('accountChoiceProvider')]
    public function testTheAnswersDecideWhetherToCreateOrLinkAnAccount(
        ?bool $hasExistingAccount,
        ?bool $keepExistingAccount,
        bool $creates,
        bool $links,
    ): void {
        $state = new OnboardingState(UserType::Lay, $hasExistingAccount, $keepExistingAccount);

        $this->assertSame($creates, $state->createsNewAccount());
        $this->assertSame($links, $state->linksExistingAccount());
    }
}
