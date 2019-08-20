<?php

namespace ApplicationTest\Library\Authentication\Identity;

use Application\Library\Authentication\Identity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * @var User
     */
    private $user;

    public function setUp() : void
    {
        $this->user = new User('ID', 'email@email.com');
    }


    public function testEmail() : void
    {
        $this->assertEquals('email@email.com', $this->user->email());
    }

    public function testSetAsAdmin() : void
    {
        $this->user->setAsAdmin();
        $this->assertEquals([0 => 'user', 1 => 'admin'], $this->user->getRoles());
    }
}
