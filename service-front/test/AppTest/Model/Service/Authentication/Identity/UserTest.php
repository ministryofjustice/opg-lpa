<?php

declare(strict_types=1);

namespace AppTest\Model\Service\Authentication\Identity;

use App\Model\Service\Authentication\Identity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testConstructorSetsExpectedProperties(): void
    {
        $lastLogin = new DateTime('2024-01-02T03:04:05+00:00');
        $expectedExpiry = time() + 3600;

        $user = new User('user-123', 'token-123', 3600, $lastLogin);

        $this->assertSame('user-123', $user->id());
        $this->assertSame('token-123', $user->token());
        $this->assertSame($lastLogin, $user->lastLogin());
        $this->assertEqualsWithDelta($expectedExpiry, $user->tokenExpiresAt()->getTimestamp(), 2);
    }

    public function testNullLastLoginDefaultsToNow(): void
    {
        $before = time();

        $user = new User('user-123', 'token-123', 60, null);

        $this->assertGreaterThanOrEqual($before, $user->lastLogin()->getTimestamp());
        $this->assertLessThanOrEqual(time(), $user->lastLogin()->getTimestamp());
    }

    public function testIsAdminReturnsFalseByDefault(): void
    {
        $user = new User('user-123', 'token-123', 60, new DateTime('2024-01-01T00:00:00+00:00'));

        $this->assertFalse($user->isAdmin());
    }

    public function testIsAdminReturnsTrueWhenConstructedAsAdmin(): void
    {
        $user = new User('user-123', 'token-123', 60, new DateTime('2024-01-01T00:00:00+00:00'), true);

        $this->assertTrue($user->isAdmin());
    }

    public function testSetTokenUpdatesToken(): void
    {
        $user = new User('user-123', 'token-123', 60, new DateTime('2024-01-01T00:00:00+00:00'));

        $user->setToken('updated-token');

        $this->assertSame('updated-token', $user->token());
    }

    public function testTokenExpiresInUpdatesExpiryTime(): void
    {
        $user = new User('user-123', 'token-123', 60, new DateTime('2024-01-01T00:00:00+00:00'));
        $expectedExpiry = time() + 120;

        $user->tokenExpiresIn(120);

        $this->assertEqualsWithDelta($expectedExpiry, $user->tokenExpiresAt()->getTimestamp(), 2);
    }

    public function testRolesReturnExpectedValues(): void
    {
        $standardUser = new User('user-123', 'token-123', 60, new DateTime('2024-01-01T00:00:00+00:00'));
        $adminUser = new User('admin-123', 'token-123', 60, new DateTime('2024-01-01T00:00:00+00:00'), true);

        $this->assertSame(['user'], $standardUser->roles());
        $this->assertSame(['user', 'admin'], $adminUser->roles());
    }

    public function testToArrayReturnsExpectedKeys(): void
    {
        $lastLogin = new DateTime('2024-01-02T03:04:05+00:00');
        $user = new User('user-123', 'token-123', 60, $lastLogin, true);

        $data = $user->toArray();

        $this->assertSame('user-123', $data['id']);
        $this->assertSame('token-123', $data['token']);
        $this->assertSame($lastLogin, $data['lastLogin']);
        $this->assertSame(['user', 'admin'], $data['roles']);
        $this->assertArrayHasKey('tokenExpiresAt', $data);
        $this->assertInstanceOf(DateTime::class, $data['tokenExpiresAt']);
    }
}
