<?php

namespace ApplicationTest\Model\Service\ApiClient\Response;

use Application\Model\Service\ApiClient\Response\AuthResponse;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class AuthResponseTest extends MockeryTestCase
{
    public function testBuildFromResponse(): void
    {
        $result = AuthResponse::buildFromResponse([
            'userId' => 'User ID',
            'token' => 'Token',
            'last_login' => 'Last Login',
            'username' => 'Username',
            'expiresIn' => 'Expires In',
            'expiresAt' => 'Expires At',
            'inactivityFlagsCleared' => true]);

        $this->assertInstanceOf(AuthResponse::class, $result);
        $this->assertEquals('User ID', $result->getUserId());
        $this->assertEquals('Token', $result->getToken());
        $this->assertEquals('Last Login', $result->getLastLogin());
        $this->assertEquals('Username', $result->getUsername());
        $this->assertEquals('Expires In', $result->getExpiresIn());
        $this->assertEquals('Expires At', $result->getExpiresAt());
        $this->assertEquals(true, $result->getInactivityFlagsCleared());
    }

    public function testBuildFromResponseEmptyArray(): void
    {
        $result = AuthResponse::buildFromResponse([]);
        $this->assertInstanceOf(AuthResponse::class, $result);
    }
}
