<?php

namespace ApplicationTest\Model\Service\Authentication\Identity;

use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Model\Service\ServiceTestHelper;
use DateInterval;
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * @var $user User
     */
    public $user;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        $this->user = new User('User ID', 'test token', 1, new DateTime('2019-01-02'), true);
    }

    /**
     * @throws Exception
     */
    public function testConstructorNeverLoggedIn() : void
    {
        $user = new User('User ID', 'test token', 1, new DateTime('2010-01-01'), true);

        ServiceTestHelper::assertTimeNear(new DateTime('now'), $user->lastLogin());
    }

    /**
     * @throws Exception
     */
    public function testConstructorNotAdmin() : void
    {
        $user = new User('User ID', 'test token', 1, new DateTime('2019-01-01'), false);

        $this->assertEquals(['user'], $user->roles());
    }

    public function testId()
    {
        $this->assertEquals('User ID', $this->user->id());
    }

    public function testToken()
    {
        $this->assertEquals('test token', $this->user->token());
    }

    public function testSetToken()
    {
        $this->user->setToken('new token');

        $this->assertEquals('new token', $this->user->token());
    }

    /**
     * @throws Exception
     */
    public function testLastLogin()
    {
        $this->assertEquals(new DateTime('2019-01-02'), $this->user->lastLogin());
    }

    /**
     * @throws Exception
     */
    public function testTokenExpiresAt()
    {
        ServiceTestHelper::assertTimeNear(
            (new DateTime('now'))->add(new DateInterval('PT1S')),
            $this->user->tokenExpiresAt()
        );
    }

    public function testRoles()
    {
        $this->assertEquals(['user', 'admin'], $this->user->roles());
    }

    public function testIsAdmin()
    {
        $this->assertEquals(true, $this->user->isAdmin());
    }

    /**
     * @throws Exception
     */
    public function testIsAdminFalse()
    {
        $user = new User('User ID', 'test token', 1, new DateTime('2010-01-01'));

        $this->assertEquals(false, $user->isAdmin());
    }

    /**
     * @throws Exception
     */
    public function testTokenExpiresIn()
    {
        $this->user->tokenExpiresIn(30);

        $thirtySecondsLater = (new DateTime('now'))->add(new DateInterval('PT30S'));

        ServiceTestHelper::assertTimeNear($thirtySecondsLater, $this->user->tokenExpiresAt());
    }

    /**
     * @throws Exception
     */
    public function testToArray()
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
