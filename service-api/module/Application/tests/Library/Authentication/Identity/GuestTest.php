<?php

namespace ApplicationTest\Library\Authentication\Identity;

use Application\Library\Authentication\Identity\Guest;
use PHPUnit\Framework\TestCase;

class GuestTest extends TestCase
{
    public function testGuestDefaultRole() : void
    {
        $guest = new Guest();
        $this->assertEquals([0 => 'guest'], $guest->getRoles());
    }
}
