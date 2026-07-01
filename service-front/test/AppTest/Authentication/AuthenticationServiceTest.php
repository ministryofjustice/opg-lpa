<?php

declare(strict_types=1);

namespace AppTest\Authentication;

use App\Authentication\Adapter\LpaAuthAdapter;
use App\Authentication\AuthenticationService;
use App\Storage\MezzioSessionStorage;
use App\Model\Service\Authentication\Identity\User;
use Laminas\Authentication\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AuthenticationServiceTest extends TestCase
{
    private LpaAuthAdapter&MockObject $adapter;
    private MezzioSessionStorage&MockObject $storage;
    private AuthenticationService $service;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(LpaAuthAdapter::class);
        $this->storage = $this->createMock(MezzioSessionStorage::class);

        $this->service = new AuthenticationService($this->adapter);
        $this->service->setStorage($this->storage);
    }

    public function testSetEmailDelegatesToAdapter(): void
    {
        $this->adapter->expects($this->once())
            ->method('setEmail')
            ->with('test@example.com');

        $result = $this->service->setEmail('test@example.com');

        $this->assertSame($this->service, $result);
    }

    public function testSetPasswordDelegatesToAdapter(): void
    {
        $this->adapter->expects($this->once())
            ->method('setPassword')
            ->with('secret');

        $result = $this->service->setPassword('secret');

        $this->assertSame($this->service, $result);
    }

    public function testAuthenticateWritesIdentityToStorageOnSuccess(): void
    {
        $identity = $this->createMock(User::class);
        $authResult = new Result(Result::SUCCESS, $identity);

        $this->adapter->method('authenticate')->willReturn($authResult);

        $this->storage->expects($this->once())
            ->method('write')
            ->with($identity);

        $result = $this->service->authenticate();

        $this->assertTrue($result->isValid());
        $this->assertSame($identity, $result->getIdentity());
    }

    public function testAuthenticateDoesNotWriteStorageOnFailure(): void
    {
        $authResult = new Result(Result::FAILURE_CREDENTIAL_INVALID, null);

        $this->adapter->method('authenticate')->willReturn($authResult);

        $this->storage->expects($this->never())->method('write');

        $result = $this->service->authenticate();

        $this->assertFalse($result->isValid());
    }

    public function testVerifyReturnsTrueAndPersistsIdentityOnSuccess(): void
    {
        $identity = $this->createMock(User::class);
        $authResult = new Result(Result::SUCCESS, $identity);

        $this->adapter->expects($this->once())
            ->method('authenticate')
            ->willReturn($authResult);

        $this->storage->expects($this->once())
            ->method('write')
            ->with($identity);

        $this->assertTrue($this->service->verify());
    }

    public function testVerifyReturnsFalseAndDoesNotWriteStorageOnFailure(): void
    {
        $authResult = new Result(Result::FAILURE_CREDENTIAL_INVALID, null);

        $this->adapter->expects($this->once())
            ->method('authenticate')
            ->willReturn($authResult);

        $this->storage->expects($this->never())->method('write');

        $this->assertFalse($this->service->verify());
    }

    public function testGetIdentityReadsFromStorage(): void
    {
        $identity = $this->createMock(User::class);

        $this->storage->method('read')->willReturn($identity);

        $this->assertSame($identity, $this->service->getIdentity());
    }

    public function testGetIdentityReturnsNullWhenStorageEmpty(): void
    {
        $this->storage->method('read')->willReturn(null);

        $this->assertNull($this->service->getIdentity());
    }

    public function testGetIdentityReturnsNullWhenNoStorageSet(): void
    {
        $service = new AuthenticationService($this->adapter);
        // No setStorage() call

        $this->assertNull($service->getIdentity());
    }

    public function testHasIdentityReturnsTrueWhenIdentityPresent(): void
    {
        $identity = $this->createMock(User::class);
        $this->storage->method('read')->willReturn($identity);

        $this->assertTrue($this->service->hasIdentity());
    }

    public function testHasIdentityReturnsFalseWhenNoIdentity(): void
    {
        $this->storage->method('read')->willReturn(null);

        $this->assertFalse($this->service->hasIdentity());
    }

    public function testClearIdentityDelegatesToStorage(): void
    {
        $this->storage->expects($this->once())->method('clear');

        $this->service->clearIdentity();
    }

    public function testGetSessionExpiryReturnsRemainingSeconds(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('abc123');

        $this->storage->method('read')->willReturn($identity);

        $this->adapter->expects($this->once())
            ->method('getSessionExpiry')
            ->with('abc123')
            ->willReturn(['valid' => true, 'remainingSeconds' => 1200]);

        $this->assertSame(1200, $this->service->getSessionExpiry());
    }

    public function testGetSessionExpiryReturnsNullWhenNoIdentity(): void
    {
        $this->storage->method('read')->willReturn(null);

        $this->assertNull($this->service->getSessionExpiry());
    }

    public function testGetSessionExpiryReturnsNullWhenTokenEmpty(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('');

        $this->storage->method('read')->willReturn($identity);

        $this->assertNull($this->service->getSessionExpiry());
    }

    public function testGetSessionExpiryReturnsNullWhenAdapterReturnsInvalid(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('abc123');

        $this->storage->method('read')->willReturn($identity);
        $this->adapter->method('getSessionExpiry')->willReturn(['valid' => false]);

        $this->assertNull($this->service->getSessionExpiry());
    }

    public function testGetSessionExpiryReturnsNullWhenAdapterReturnsUnexpected(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('abc123');

        $this->storage->method('read')->willReturn($identity);
        $this->adapter->method('getSessionExpiry')->willReturn(null);

        $this->assertNull($this->service->getSessionExpiry());
    }

    public function testSetSessionExpiryReturnsRemainingSeconds(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('abc123');

        $this->storage->method('read')->willReturn($identity);

        $this->adapter->expects($this->once())
            ->method('setSessionExpiry')
            ->with('abc123', 600)
            ->willReturn(['valid' => true, 'remainingSeconds' => 600]);

        $this->assertSame(600, $this->service->setSessionExpiry(600));
    }

    public function testSetSessionExpiryReturnsNullWhenNoToken(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('');

        $this->storage->method('read')->willReturn($identity);

        $this->assertNull($this->service->setSessionExpiry(600));
    }

    public function testSetSessionExpiryReturnsNullWhenAdapterFails(): void
    {
        $identity = $this->createMock(User::class);
        $identity->method('token')->willReturn('abc123');

        $this->storage->method('read')->willReturn($identity);
        $this->adapter->method('setSessionExpiry')->willReturn(null);

        $this->assertNull($this->service->setSessionExpiry(600));
    }
}
