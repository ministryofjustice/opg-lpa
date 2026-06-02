<?php

declare(strict_types=1);

namespace AppTest\Storage;

use App\Storage\MezzioSessionStorage;
use App\Model\Service\Authentication\Identity\User;
use DateTime;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MezzioSessionStorageTest extends TestCase
{
    private SessionInterface&MockObject $session;
    private MezzioSessionStorage $storage;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->storage = new MezzioSessionStorage();
    }

    public function testIsEmptyWhenNoSessionSet(): void
    {
        $this->assertTrue($this->storage->isEmpty());
    }

    public function testIsEmptyWhenSessionHasNoIdentityKey(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->storage->setSession($this->session);

        $this->assertTrue($this->storage->isEmpty());
    }

    public function testIsNotEmptyWhenSessionHasIdentity(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);
        $this->storage->setSession($this->session);

        $this->assertFalse($this->storage->isEmpty());
    }

    public function testReadReturnsNullWhenNoSession(): void
    {
        $this->assertNull($this->storage->read());
    }

    public function testReadReturnsNullWhenNoIdentityKey(): void
    {
        $this->session->method('has')->with('identity')->willReturn(false);
        $this->storage->setSession($this->session);

        $this->assertNull($this->storage->read());
    }

    public function testReadReturnsNullWhenIdentityDataInvalid(): void
    {
        $this->session->method('has')->with('identity')->willReturn(true);
        $this->session->method('get')->with('identity')->willReturn(['incomplete' => 'data']);
        $this->storage->setSession($this->session);

        $this->assertNull($this->storage->read());
    }

    public function testReadReturnsUserWithCorrectData(): void
    {
        $tokenExpiresAt = new DateTime('+1 hour');
        $lastLogin = new DateTime('2026-01-01T10:00:00+00:00');

        $this->session->method('has')->with('identity')->willReturn(true);
        $this->session->method('get')->with('identity')->willReturn([
            'userId'         => 'user-123',
            'token'          => 'my-token',
            'tokenExpiresAt' => $tokenExpiresAt->format('c'),
            'lastLogin'      => $lastLogin->format('c'),
        ]);
        $this->storage->setSession($this->session);

        $user = $this->storage->read();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user-123', $user->id());
        $this->assertEquals('my-token', $user->token());
        $this->assertNotNull($user->tokenExpiresAt());
    }

    public function testWriteDoesNothingWithNoSession(): void
    {
        $user = new User('user-1', 'token', 3600, new DateTime());
        // Should not throw
        $this->storage->write($user);
        $this->assertTrue(true);
    }

    public function testWriteDoesNothingWithNonUserContents(): void
    {
        $this->session->expects($this->never())->method('set');
        $this->storage->setSession($this->session);

        $this->storage->write('not a user');
    }

    public function testWritePersistsUserDataToSession(): void
    {
        $lastLogin = new DateTime('2026-01-01T10:00:00+00:00');
        $user = new User('user-123', 'my-token', 3600, $lastLogin);

        $this->session->expects($this->once())
            ->method('set')
            ->with('identity', $this->callback(function ($data) {
                return $data['userId'] === 'user-123'
                    && $data['token'] === 'my-token'
                    && isset($data['tokenExpiresAt'])
                    && isset($data['lastLogin']);
            }));
        $this->storage->setSession($this->session);

        $this->storage->write($user);
    }

    public function testClearDoesNothingWithNoSession(): void
    {
        // Should not throw
        $this->storage->clear();
        $this->assertTrue(true);
    }

    public function testClearUnsetsIdentityKey(): void
    {
        $this->session->expects($this->once())->method('unset')->with('identity');
        $this->storage->setSession($this->session);

        $this->storage->clear();
    }
}
