<?php

namespace ApplicationTest\Library\Authentication\Identity;

use Application\Library\Authentication\Identity\AbstractIdentity;
use Library\Authentication\Identity\TestableAbstractIdentity;
use PHPUnit\Framework\TestCase;

class AbstractIdentityTest extends TestCase
{
    /**
     * @var AbstractIdentity
     */
    private $abstractIdentity;

    public function setUp() : void
    {
        $this->abstractIdentity = new TestableAbstractIdentity('ID', [0 => 'A role', 1 => 'Another role']);
    }

    public function testId() : void
    {
        $this->assertEquals('ID', $this->abstractIdentity->id());
    }

    public function testGetRoles() : void
    {
        $this->assertEquals([0 => 'A role', 1 => 'Another role'], $this->abstractIdentity->getRoles());
    }
}
