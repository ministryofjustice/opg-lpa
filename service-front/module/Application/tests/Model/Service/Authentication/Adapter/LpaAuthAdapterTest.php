<?php

namespace ApplicationTest\Model\Service\Authentication\Adapter;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Model\Service\ServiceTestHelper;
use DateTime;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;
use Laminas\Authentication\Result;

class LpaAuthAdapterTest extends MockeryTestCase
{
    /**
     * @var $client Client|MockInterface
     */
    private $client;

    /**
     * @var $adapter LpaAuthAdapter
     */
    private $adapter;

    public function setUp(): void
    {
        $this->client = Mockery::mock(Client::class);

        $this->adapter = new LpaAuthAdapter($this->client);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticate(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([
                'userId' => 'User ID',
                'token' => 'test token',
                'last_login' => '2018-01-02 T00:00:00.000'
            ]);

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(1, $result->getCode());
        $this->assertEquals(
            new User('User ID', 'test token', null, new DateTime('2018-01-02 T00:00:00.000')),
            $result->getIdentity()
        );
        $this->assertEquals([], $result->getMessages());
    }

    public function testAuthenticateNoPreviousLogin(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([
                'userId' => 'User ID',
                'token' => 'test token'
            ]);

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(1, $result->getCode());
        $this->assertEquals([], $result->getMessages());

        $expectedUser = new User('User ID', 'test token', null, null);
        $actualUser = $result->getIdentity();

        $this->assertEquals($expectedUser->id(), $actualUser->id());
        $this->assertEquals($expectedUser->token(), $actualUser->token());
        $this->assertInstanceOf(DateTime::class, $actualUser->lastLogin());
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateInactivityFlagsCleared(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([
                'userId' => 'User ID',
                'token' => 'test token',
                'last_login' => '2018-01-02 T00:00:00.000',
                'inactivityFlagsCleared' => true
            ]);

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(1, $result->getCode());
        $this->assertEquals(
            new User('User ID', 'test token', null, new DateTime('2018-01-02 T00:00:00.000')),
            $result->getIdentity()
        );
        $this->assertEquals(['inactivity-flags-cleared'], $result->getMessages());
    }

    public function testAuthenticateNotAuthenticated(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([]);

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals([null], $result->getMessages());
    }

    public function testAuthenticateEmailNotSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email address not set');

        $this->adapter->authenticate();
    }

    public function testAuthenticatePasswordNotSet(): void
    {
        $this->adapter->setEmail('test@email.com');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Password not set');

        $this->adapter->authenticate();
    }

    public function testAuthenticationApiException(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Invalid user', 401));

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['authentication-failed'], $result->getMessages());
    }

    public function testAuthenticationApiExceptionAccountLocked(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('account-locked/max-login-attempts', 401));

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['locked'], $result->getMessages());
    }

    public function testAuthenticationApiExceptionAccountNotActive(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('account-not-active', 401));

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['not-activated'], $result->getMessages());
    }

    public function testAuthenticationApi500Exception(): void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('api-error', 500));

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['api-error'], $result->getMessages());
    }

    public function testGetSessionExpiry(): void
    {
        $this->client->shouldReceive('httpGet')
            ->withArgs(['/v2/session-expiry', [], true, true, ['CheckedToken' => 'test token']])
            ->once()
            ->andReturn(['test' => 'response']);

        $result = $this->adapter->getSessionExpiry('test token');

        $this->assertEquals(['test' => 'response'], $result);
    }

    public function testGetSessionExpiryApiException(): void
    {
        $this->client->shouldReceive('httpGet')
            ->withArgs(['/v2/session-expiry', [], true, true, ['CheckedToken' => 'test token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('api-error', 500));

        $result = $this->adapter->getSessionExpiry('test token');

        $this->assertEquals(null, $result);
    }

    public function testSetSessionExpiry(): void
    {
        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/session-set-expiry', ['expireInSeconds' => 20], ['CheckedToken' => 'test token']])
            ->once()
            ->andReturn(['test' => 'response']);

        $result = $this->adapter->setSessionExpiry('test token', 20);

        $this->assertEquals(['test' => 'response'], $result);
    }

    public function testSetSessionExpiryApiException(): void
    {
        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/session-set-expiry', ['expireInSeconds' => 20], ['CheckedToken' => 'test token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('unexpected-error', 500));

        $result = $this->adapter->setSessionExpiry('test token', 20);

        $this->assertEquals('unexpected-error', $result);
    }
}
