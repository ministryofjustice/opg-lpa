<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Users\Entity as UserEntity;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use MongoCollection;
use MongoCursor;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Service\AuthorizationService;

class ResourceBuilder
{
    private $lpa;
    private $user;
    private $authorizationService;
    private $lpaCollection;
    private $locked = false;
    private $updateNumberModified = null;
    private $insert = false;

    /**
     * @return TestableResource
     */
    public function build()
    {
        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('info');

        $this->lpaCollection = Mockery::mock(MongoCollection::class);

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);
        $serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-lpa')->andReturn($this->lpaCollection);

        $resource = new TestableResource();
        $resource->setServiceLocator($serviceLocatorMock);

        if ($this->authorizationService === null) {
            $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
            $authorizationServiceMock->shouldReceive('isGranted')->andReturn(true);
            $resource->setAuthorizationService($authorizationServiceMock);
        } else {
            $resource->setAuthorizationService($this->authorizationService);
        }

        if ($this->user !== null) {
            $resource->setRouteUser(new UserEntity($this->user));

            if ($this->lpa !== null) {
                $this->lpaCollection->shouldReceive('findOne')
                    ->with(['_id' => (int)$this->lpa->id, 'user' => $this->user->id])
                    ->andReturn($this->lpa->toMongoArray());

                if ($this->locked) {
                    $this->lpaCollection->shouldReceive('find')
                        ->with([ '_id'=>$this->lpa->id, 'locked'=>true ], [ '_id'=>true ])
                        ->andReturn($this->getSingleCursor());
                } else {
                    $this->lpaCollection->shouldReceive('find')
                        ->with([ '_id'=>$this->lpa->id, 'locked'=>true ], [ '_id'=>true ])
                        ->andReturn($this->getDefaultCursor());
                }
            }
        }

        if ($this->lpa === null) {
            $this->lpaCollection->shouldNotReceive('findOne');
            $this->lpaCollection->shouldNotReceive('find');
        }

        $this->lpaCollection->shouldReceive('findOne')->andReturn(null);

        if ($this->insert) {
            $this->lpaCollection->shouldReceive('insert')->once();
        } else {
            $this->lpaCollection->shouldNotReceive('insert');
        }

        if ($this->updateNumberModified === null) {
            $this->lpaCollection->shouldNotReceive('update');
        } else {
            $this->lpaCollection->shouldReceive('update')->once()->andReturn(['nModified' => $this->updateNumberModified]);
        }

        return $resource;
    }

    /**
     * @param Lpa $lpa
     * @return ResourceBuilder
     */
    public function withLpa(Lpa $lpa)
    {
        $this->lpa = $lpa;
        return $this;
    }

    /**
     * @param User $user
     * @return ResourceBuilder
     */
    public function withUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param MockInterface $authorizationService
     * @return ResourceBuilder
     */
    public function withAuthorizationService(MockInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
        return $this;
    }

    /**
     * @param bool $locked
     * @return ResourceBuilder
     */
    public function withLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @param bool $insert
     * @return ResourceBuilder
     */
    public function withInsert($insert)
    {
        $this->insert = $insert;
        return $this;
    }

    /**
     * @param int $updateNumberModified
     * @return ResourceBuilder
     */
    public function withUpdateNumberModified($updateNumberModified)
    {
        $this->updateNumberModified = $updateNumberModified;
        return $this;
    }

    public function verify()
    {
        Mockery::close();
    }

    /**
     * @return MockInterface
     */
    private function getDefaultCursor()
    {
        $defaultCursor = Mockery::mock(MongoCursor::class);
        $defaultCursor->shouldReceive('limit')->andReturn($defaultCursor);
        $defaultCursor->shouldReceive('count')->andReturn(0);
        return $defaultCursor;
    }

    /**
     * @return MockInterface
     */
    private function getSingleCursor()
    {
        $singleCursor = Mockery::mock(MongoCursor::class);
        $singleCursor->shouldReceive('limit')->andReturn($singleCursor);
        $singleCursor->shouldReceive('count')->andReturn(1);
        return $singleCursor;
    }
}