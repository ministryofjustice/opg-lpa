<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Authentication\Identity;

use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Model\Service\ServiceTestHelper;
use DateInterval;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private User $user;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->user = new User('User ID', 'test token', 1, new DateTime('2019-01-02'), true);
    }

    /**
     * @throws Exception
     */
    public function testConstructorNeverLoggedIn(): void
    {
        $user = new User('User ID', 'test token', 1, null, true);
        ServiceTestHelper::assertTimeNear(new DateTime('now'), $user->lastLogin(), 5);
    }

    /**
     * @throws Exception
     */
    public function testConstructorNotAdmin(): void
    {
        $user = new User('User ID', 'test token', 1, new DateTime('2019-01-01'), false);

        $this->assertEquals(['user'], $user->roles());
    }

    public function testId(): void
    {
        $this->assertEquals('User ID', $this->user->id());
    }

    public function testToken(): void
    {
        $this->assertEquals('test token', $this->user->token());
    }

    public function testSetToken(): void
    {
        $this->user->setToken('new token');

        $this->assertEquals('new token', $this->user->token());
    }

    /**
     * @throws Exception
     */
    public function testLastLogin(): void
    {
        $this->assertEquals(new DateTime('2019-01-02'), $this->user->lastLogin());
    }

    /**
     * @throws Exception
     */
    public function testTokenExpiresAt(): void
    {
        ServiceTestHelper::assertTimeNear(
            (new DateTime('now'))->add(new DateInterval('PT1S')),
            $this->user->tokenExpiresAt()
        );
    }

    public function testRoles(): void
    {
        $this->assertEquals(['user', 'admin'], $this->user->roles());
    }

    public function testIsAdmin(): void
    {
        $this->assertEquals(true, $this->user->isAdmin());
    }

    /**
     * @throws Exception
     */
    public function testIsAdminFalse(): void
    {
        $user = new User('User ID', 'test token', 1, new DateTime('2010-01-01'));

        $this->assertEquals(false, $user->isAdmin());
    }

    /**
     * @throws Exception
     */
    public function testTokenExpiresIn(): void
    {
        $this->user->tokenExpiresIn(30);

        $thirtySecondsLater = (new DateTime('now'))->add(new DateInterval('PT30S'));

        ServiceTestHelper::assertTimeNear($thirtySecondsLater, $this->user->tokenExpiresAt());
    }

    /**
     * @throws Exception
     */
    public function testToArray(): void
    {
        $result = $this->user->toArray();

        //Test separately so that dates can be handled
        $this->assertEquals('User ID', $result['id']);
        $this->assertEquals('test token', $result['token']);
        ServiceTestHelper::assertTimeNear(new DateTime('now'), $result['tokenExpiresAt']);
        $this->assertEquals(new DateTime('2019-01-02'), $result['lastLogin']);
        $this->assertEquals(['user', 'admin'], $result['roles']);
    }
}
