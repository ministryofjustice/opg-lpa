<?php

namespace ApplicationTest\Library\Authorization\Assertions;

use Application\Library\Authentication\Identity\IdentityInterface;
use Application\Library\Authorization\Assertions\IsAuthorizedToManageUser;
use Lmc\Rbac\Identity\IdentityInterface as LbacIdentityInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class IsAuthorizedToManageUserTest extends MockeryTestCase
{
    private IsAuthorizedToManageUser $isAuthorisedToManageUser;

    public function setUp(): void
    {
        $this->isAuthorisedToManageUser = new IsAuthorizedToManageUser();
    }

    public function testAssertValid(): void
    {
        $userIdentity = Mockery::mock(IdentityInterface::class);
        $userIdentity->shouldReceive('id')->andReturn('route user id')->once();

        $result = $this->isAuthorisedToManageUser->assert('', $userIdentity, 'route user id');
        $this->assertTrue($result);
    }

    public function testAssertNoRouteUserId(): void
    {
        $result = $this->isAuthorisedToManageUser->assert('', null, null);
        $this->assertFalse($result);
    }

    public function testAssertTokenUserHasNoIdMethod(): void
    {
        $userIdentity = new class implements LbacIdentityInterface {
            public function getRoles(): iterable
            {
                return [];
            }
        };

        $result = $this->isAuthorisedToManageUser->assert('', $userIdentity, 'route user id');
        $this->assertFalse($result);
    }

    public function testAssertRouteUserIdDoesNotMatch(): void
    {
        $userIdentity = Mockery::mock(IdentityInterface::class);
        $userIdentity->shouldReceive('id')->andReturn('not route user id')->once();

        $result = $this->isAuthorisedToManageUser->assert('', $userIdentity, 'route user id');
        $this->assertFalse($result);
    }
}
