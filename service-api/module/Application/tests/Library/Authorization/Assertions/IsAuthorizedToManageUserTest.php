<?php

namespace ApplicationTest\Library\Authorization\Assertions;

use Application\Library\Authentication\Identity\IdentityInterface;
use Application\Library\Authorization\Assertions\IsAuthorizedToManageUser;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use ZfcRbac\Service\AuthorizationService;

class IsAuthorizedToManageUserTest extends MockeryTestCase
{
    /**
     * @var IsAuthorizedToManageUser
     */
    private $isAuthorisedToManageUser;

    /**
     * @var AuthorizationService|MockInterface
     */
    private $authorisationService;

    public function setUp() : void
    {
        $this->isAuthorisedToManageUser = new IsAuthorizedToManageUser();

        $this->authorisationService = Mockery::mock(AuthorizationService::class);
    }

    public function testAssertValid() : void
    {
        $userIdentity = Mockery::mock(IdentityInterface::class);
        $userIdentity->shouldReceive('id')->andReturn('route user id')->once();

        $this->authorisationService->shouldReceive('getIdentity')->andReturn($userIdentity)->once();

        $result = $this->isAuthorisedToManageUser->assert($this->authorisationService, 'route user id');
        $this->assertTrue($result);
    }

    public function testAssertNoRouteUserId() : void
    {
        $result = $this->isAuthorisedToManageUser->assert($this->authorisationService, null);
        $this->assertFalse($result);
    }

    public function testAssertTokenUserHasNoIdMethod() : void
    {
        $this->authorisationService->shouldReceive('getIdentity')->andReturn([])->once();

        $result = $this->isAuthorisedToManageUser->assert($this->authorisationService, 'route user id');
        $this->assertFalse($result);
    }

    public function testAssertRouteUserIdDoesNotMatch() : void
    {
        $userIdentity = Mockery::mock(IdentityInterface::class);
        $userIdentity->shouldReceive('id')->andReturn('not route user id')->once();

        $this->authorisationService->shouldReceive('getIdentity')->andReturn($userIdentity)->once();

        $result = $this->isAuthorisedToManageUser->assert($this->authorisationService, 'route user id');
        $this->assertFalse($result);
    }

}
