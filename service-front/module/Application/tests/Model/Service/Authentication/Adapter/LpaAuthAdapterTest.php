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
use Zend\Authentication\Result;

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

    public function setUp() : void
    {
        $this->client = Mockery::mock(Client::class);

        $this->adapter = new LpaAuthAdapter($this->client);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticate() : void
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

    /**
     * @throws Exception
     */
    public function testAuthenticateInactivityFlagsCleared() : void
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

    public function testAuthenticateNotAuthenticated() : void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([]);

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals([null], $result->getMessages());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Email address not set
     */
    public function testAuthenticateEmailNotSet() : void
    {
        $this->adapter->authenticate();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Password not set
     */
    public function testAuthenticatePasswordNotSet() : void
    {
        $this->adapter->setEmail('test@email.com');

        $this->adapter->authenticate();
    }

    public function testAuthenticationApiException() : void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['authentication-failed'], $result->getMessages());
    }

    public function testAuthenticationApiExceptionAccountLocked() : void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('account-locked/max-login-attempts'));

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['locked'], $result->getMessages());
    }

    public function testAuthenticationApiExceptionAccountNotActive() : void
    {
        $this->adapter->setEmail('test@email.com');
        $this->adapter->setPassword('test-password');

        $this->client->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('account-not-active'));

        $result = $this->adapter->authenticate();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(0, $result->getCode());
        $this->assertNull($result->getIdentity());
        $this->assertEquals(['not-activated'], $result->getMessages());
    }

    /**
     * @throws \Http\Client\Exception
     */
    public function testGetSessionExpiry() : void
    {
        $this->client->shouldReceive('httpGet')
            ->withArgs(['/v2/session-expiry', [], true, true, ['CheckedToken' => 'test token']])
            ->once()
            ->andReturn(['test' => 'response']);

        $result = $this->adapter->getSessionExpiry('test token');

        $this->assertEquals(['test' => 'response'], $result);
    }
}
