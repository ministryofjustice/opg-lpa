<?php

namespace ApplicationTest;

use Application\DataAccess\Mongo\DateCallback;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Users\Entity as UserEntity;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Collection;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\Logger;
use OpgTest\Lpa\DataModel\FixturesData;
use ZfcRbac\Service\AuthorizationService;

abstract class AbstractResourceTest extends MockeryTestCase
{
    /**
     * @var MockInterface|Collection
     */
    protected $lpaCollection;

    /**
     * @var MockInterface|AuthorizationService
     */
    protected $authorizationService;

    /**
     * @var MockInterface|Logger
     */
    protected $logger;

    protected function setUp()
    {
        $this->lpaCollection = Mockery::mock(Collection::class);

        $this->authorizationService = Mockery::mock(AuthorizationService::class);

        $this->logger = Mockery::mock(Logger::class);
    }

    protected function setUpCheckAccessTest(AbstractResource $resource)
    {
        $this->setCheckAccessExpectations($resource, FixturesData::getUser(), false);

        //Should not be authorised for any Resource method
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this resource');
    }

    protected function setCheckAccessExpectations(
        AbstractResource $resource,
        User $user,
        bool $isAuthenticated = true,
        bool $isAuthorizedToManageUser = true,
        int $times = 1
    ) {
        $userEntity = new UserEntity($user);
        $resource->setRouteUser($userEntity);

        $this->logger->shouldReceive('info')
            ->withArgs(['Access allowed for user', ['userid' => $userEntity->userId()]])->times($times);

        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times($times)
            ->andReturn($isAuthenticated);

        if ($isAuthenticated === true) {
            $this->authorizationService->shouldReceive('isGranted')
                ->withArgs(['isAuthorizedToManageUser', $userEntity->userId()])->times($times)
                ->andReturn($isAuthorizedToManageUser);
        }
    }

    /**
     * @param User $user
     * @param int $lpaId
     */
    protected function setFindNullLpaExpectation(User $user, int $lpaId)
    {
        $this->lpaCollection->shouldReceive('findOne')
            ->withArgs([['_id' => $lpaId, 'user' => $user->id]])->once()
            ->andReturn(null);
    }

    /**
     * @param User $user
     * @param Lpa $lpa
     */
    protected function setFindOneLpaExpectation(User $user, Lpa $lpa)
    {
        $this->lpaCollection->shouldReceive('findOne')
            ->withArgs([['_id' => $lpa->id, 'user' => $user->id]])->once()
            ->andReturn($lpa->toArray(new DateCallback()));
    }
}
