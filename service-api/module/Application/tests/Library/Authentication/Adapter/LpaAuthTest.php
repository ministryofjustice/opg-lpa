<?php

namespace ApplicationTest\Library\Authentication\Adapter;

use Application\Library\Authentication\Adapter\LpaAuth;
use Application\Library\Authentication\Identity\User;
use Application\Model\Service\Authentication\Service;
use Laminas\Db\Exception\RuntimeException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Laminas\Authentication\Result;
use Psr\Log\LoggerInterface;

class LpaAuthTest extends MockeryTestCase
{
    /**
     * @var Service|MockInterface
     */
    private $authenticationService;

    public function setUp(): void
    {
        $this->authenticationService = Mockery::mock(Service::class);
        $this->logger = Mockery::spy(LoggerInterface::class);
    }

    public function testAuthenticateStandardUser(): void
    {
        $this->authenticationService->shouldReceive('withToken')->with('Token', true)
            ->andReturn(['userId' => 'ID', 'username' => 'user name']);

        $lpaAuth = new LpaAuth($this->authenticationService, 'Token', []);
        $lpaAuth->setLogger($this->logger);
        $result = $lpaAuth->authenticate();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getCode());

        $user = $result->getIdentity();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('ID', $user->id());
        $this->assertEquals('user name', $user->email());
        $this->assertEquals([0 => 'user'], $user->getRoles());
    }

    public function testAuthenticateAdminUser(): void
    {
        $this->authenticationService->shouldReceive('withToken')->with('Token', true)
            ->andReturn(['userId' => 'ID', 'username' => 'user name']);

        $lpaAuth = new LpaAuth($this->authenticationService, 'Token', ['user name']);
        $lpaAuth->setLogger($this->logger);
        $result = $lpaAuth->authenticate();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getCode());

        $user = $result->getIdentity();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('ID', $user->id());
        $this->assertEquals('user name', $user->email());
        $this->assertEquals([0 => 'user', 1 => 'admin'], $user->getRoles());
    }

    public function testAuthenticateFailedToken(): void
    {
        $this->authenticationService->shouldReceive('withToken')->with('Token', true)
            ->andReturn(null);

        $lpaAuth = new LpaAuth($this->authenticationService, 'Token', ['user name']);
        $lpaAuth->setLogger($this->logger);
        $result = $lpaAuth->authenticate();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertNull($result->getIdentity());
    }

    public function testAuthenticateFailedDb(): void
    {
        $expectedException = new RuntimeException();

        $this->authenticationService->shouldReceive('withToken')
            ->with('Token', true)
            ->andThrow($expectedException);

        $lpaAuth = new LpaAuth($this->authenticationService, 'Token', ['user name']);
        $lpaAuth->setLogger($this->logger);

        $result = $lpaAuth->authenticate();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE, $result->getCode());
        $this->assertNull($result->getIdentity());
    }
}
