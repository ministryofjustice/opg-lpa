<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient\Response;

use App\Service\ApiClient\Response\AuthResponse;
use PHPUnit\Framework\TestCase;

final class AuthResponseTest extends TestCase
{
    public function testBuildFromResponseCreatesInstanceWithAllFields(): void
    {
        $response = AuthResponse::buildFromResponse([
            'userId' => 'user-123',
            'username' => 'user@example.com',
            'token' => 'token-123',
            'expiresIn' => '3600',
            'expiresAt' => '2024-01-01T01:00:00+00:00',
            'last_login' => '2024-01-01T00:00:00+00:00',
            'inactivityFlagsCleared' => true,
        ]);

        $this->assertInstanceOf(AuthResponse::class, $response);
        $this->assertSame('user-123', $response->getUserId());
        $this->assertSame('user@example.com', $response->getUsername());
        $this->assertSame('token-123', $response->getToken());
        $this->assertSame(3600, $response->getExpiresIn());
        $this->assertSame('2024-01-01T01:00:00+00:00', $response->getExpiresAt());
        $this->assertSame('2024-01-01T00:00:00+00:00', $response->getLastLogin());
        $this->assertTrue($response->getInactivityFlagsCleared());
    }

    public function testGettersReturnExpectedValues(): void
    {
        $response = new AuthResponse([
            'userId' => 'user-123',
            'username' => 'user@example.com',
            'token' => 'token-123',
            'expiresIn' => 120,
            'expiresAt' => 'later',
            'last_login' => 'earlier',
            'inactivityFlagsCleared' => false,
        ]);

        $this->assertSame('user-123', $response->getUserId());
        $this->assertSame('user@example.com', $response->getUsername());
        $this->assertSame('token-123', $response->getToken());
        $this->assertSame(120, $response->getExpiresIn());
        $this->assertSame('later', $response->getExpiresAt());
        $this->assertSame('earlier', $response->getLastLogin());
        $this->assertFalse($response->getInactivityFlagsCleared());
        $this->assertNull($response->getErrorDescription());
    }

    public function testIsAuthenticatedReturnsTrueWhenRequiredFieldsAreSet(): void
    {
        $response = new AuthResponse([
            'userId' => 'user-123',
            'token' => 'token-123',
        ]);

        $this->assertTrue($response->isAuthenticated());
    }

    public function testIsAuthenticatedReturnsFalseWhenUserIdIsMissing(): void
    {
        $response = new AuthResponse([
            'token' => 'token-123',
        ]);

        $this->assertFalse($response->isAuthenticated());
    }

    public function testIsAuthenticatedReturnsFalseWhenErrorDescriptionIsSet(): void
    {
        $response = (new AuthResponse([
            'userId' => 'user-123',
            'token' => 'token-123',
        ]))->setErrorDescription('Authentication failed');

        $this->assertFalse($response->isAuthenticated());
    }

    public function testSetErrorDescriptionChainsAndStoresValue(): void
    {
        $response = new AuthResponse();

        $result = $response->setErrorDescription('Authentication failed');

        $this->assertSame($response, $result);
        $this->assertSame('Authentication failed', $response->getErrorDescription());
    }
}
