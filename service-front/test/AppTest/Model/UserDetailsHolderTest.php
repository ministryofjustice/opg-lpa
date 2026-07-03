<?php

declare(strict_types=1);

namespace AppTest\Model;

use App\Model\UserDetailsHolder;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;

class UserDetailsHolderTest extends TestCase
{
    public function testGetReturnsNullByDefault(): void
    {
        $this->assertNull((new UserDetailsHolder())->get());
    }

    public function testSetStoresUserDetails(): void
    {
        $user = FixturesData::getUser();
        $holder = new UserDetailsHolder();

        $holder->set($user);

        $this->assertSame($user, $holder->get());
    }
}
