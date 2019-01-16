<?php

namespace ApplicationTest\Model\Service\ApiClient\Response;

use Application\Model\Service\ApiClient\Response\AuthResponse;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class AuthResponseTest extends MockeryTestCase
{
    public function testBuildFromResponse() : void
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
    
    public function testBuildFromResponseEmptyArray() : void
    {
        $result = AuthResponse::buildFromResponse([]);

        $this->assertInstanceOf(AuthResponse::class, $result);
    }

    public function testExchangeArray() : void
    {
        $result = AuthResponse::buildFromResponse([
            'userId' => 'User ID',
            'token' => 'Token',
            'last_login' => 'Last Login',
            'username' => 'Username',
            'expiresIn' => 'Expires In',
            'expiresAt' => 'Expires At',
            'inactivityFlagsCleared' => true]);

        $result->exchangeArray([
            'userId' => 'User ID 2',
            'token' => 'Token 3',
            'last_login' => 'Last Login 4',
            'username' => 'Username 5',
            'expiresIn' => 'Expires In 6',
            'expiresAt' => 'Expires At 7',
            'inactivityFlagsCleared' => false]);

        $this->assertInstanceOf(AuthResponse::class, $result);
        $this->assertEquals('User ID 2', $result->getUserId());
        $this->assertEquals('Token 3', $result->getToken());
        $this->assertEquals('Last Login 4', $result->getLastLogin());
        $this->assertEquals('Username 5', $result->getUsername());
        $this->assertEquals('Expires In 6', $result->getExpiresIn());
        $this->assertEquals('Expires At 7', $result->getExpiresAt());
        $this->assertEquals(false, $result->getInactivityFlagsCleared());
    }

    public function testExchangeArrayEmpty() : void
    {
        $result = AuthResponse::buildFromResponse([
            'userId' => 'User ID',
            'token' => 'Token',
            'last_login' => 'Last Login',
            'username' => 'Username',
            'expiresIn' => 'Expires In',
            'expiresAt' => 'Expires At',
            'inactivityFlagsCleared' => true]);

        $result->exchangeArray([]);

        $this->assertInstanceOf(AuthResponse::class, $result);
        $this->assertEquals(null, $result->getUserId());
        $this->assertEquals(null, $result->getToken());
        $this->assertEquals(null, $result->getLastLogin());
        $this->assertEquals(null, $result->getUsername());
        $this->assertEquals(null, $result->getExpiresIn());
        $this->assertEquals(null, $result->getExpiresAt());
        $this->assertEquals(null, $result->getInactivityFlagsCleared());
    }
}
