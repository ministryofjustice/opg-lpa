<?php

namespace ApplicationTest;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Users\Entity as UserEntity;
use Application\Library\Authentication\Identity\User as UserIdentity;
use Mockery;
use Mockery\MockInterface;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\Manager;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Traversable;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Service\AuthorizationService;

abstract class AbstractResourceBuilder
{
    const LPA_COLLECTION_NAMESPACE = 'opglpa-api.lpa';

    protected $lpa;
    protected $user;
    protected $authorizationService;
    protected $lpaCollection = null;
    protected $locked = false;
    protected $updateNumberModified = null;
    protected $config = array();
    protected $applicationsResource = null;
    protected $statsWhoCollection = null;

    protected $serviceLocatorMock = null;

    /**
     * @return AbstractResource
     */
    abstract public function build();

    /**
     * @param Lpa $lpa
     * @return $this
     */
    public function withLpa(Lpa $lpa)
    {
        $this->lpa = $lpa;
        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function withUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param MockInterface $authorizationService
     * @return $this
     */
    public function withAuthorizationService(MockInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
        return $this;
    }

    /**
     * @param bool $locked
     * @return $this
     */
    public function withLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @param int $updateNumberModified
     * @return $this
     */
    public function withUpdateNumberModified($updateNumberModified)
    {
        $this->updateNumberModified = $updateNumberModified;
        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function withConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param $applicationsResource
     * @return $this
     */
    public function withApplicationsResource($applicationsResource)
    {
        $this->applicationsResource = $applicationsResource;
        return $this;
    }

    /**
     * @param $lpaCollection
     * @return $this
     */
    public function withLpaCollection($lpaCollection)
    {
        $this->lpaCollection = $lpaCollection;
        return $this;
    }

    /**
     * @param $statsWhoCollection
     * @return $this
     */
    public function withStatsWhoCollection($statsWhoCollection)
    {
        $this->statsWhoCollection = $statsWhoCollection;
        return $this;
    }

    public function verify()
    {
        $this->lpaCollection->mockery_verify();
        Mockery::close();
    }

    protected function buildMocks(AbstractResource $resource, $addDefaults = true)
    {
        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('info');

        $this->lpaCollection = $this->lpaCollection ?: Mockery::mock(MongoCollection::class);
        $this->statsWhoCollection = $this->statsWhoCollection ?: Mockery::mock(MongoCollection::class);
        $this->mongodbManager = Mockery::mock();

        $this->serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $this->serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);
        $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-lpa')->andReturn($this->lpaCollection);
        $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-stats-who')->andReturn($this->statsWhoCollection);
        $this->serviceLocatorMock->shouldReceive('get')->with('config')->andReturn($this->config);
        $this->serviceLocatorMock->shouldReceive('get')->with('resource-applications')->andReturn($this->applicationsResource);

        $resource->setServiceLocator($this->serviceLocatorMock);

        if ($this->authorizationService === null) {
            $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
            $authorizationServiceMock->shouldReceive('isGranted')->andReturn(true);
            if ($this->user !== null) {
                $identity = new UserIdentity($this->user->id, $this->user->email);
                $authorizationServiceMock->shouldReceive('getIdentity')->andReturn($identity);
            }
            $resource->setAuthorizationService($authorizationServiceMock);
        } else {
            $resource->setAuthorizationService($this->authorizationService);
        }

        if ($this->user !== null) {
            $resource->setRouteUser(new UserEntity($this->user));

            if ($this->lpa !== null) {
                $resource->setLpa($this->lpa);

                $this->lpaCollection->shouldReceive('findOne')
                    ->with(['_id' => (int)$this->lpa->id, 'user' => $this->user->id])
                    ->andReturn($this->lpa->toMongoArray());
                $this->lpaCollection->shouldReceive('findOne')
                    ->with(['_id' => $this->lpa->id])
                    ->andReturn($this->lpa->toMongoArray());

                if ($this->locked) {
                    $this->lpaCollection->shouldReceive('count')
                        ->with([ '_id'=>$this->lpa->id, 'locked'=>true ], [ '_id'=>true ])
                        ->andReturn(1);
                } else {
                    $this->lpaCollection->shouldReceive('count')
                        ->with([ '_id'=>$this->lpa->id, 'locked'=>true ], [ '_id'=>true ])
                        ->andReturn(0);
                }
            }
        }

        if ($this->lpa === null) {
            $this->lpaCollection->shouldNotReceive('findOne');
            $this->lpaCollection->shouldNotReceive('find');
        }

        if ($addDefaults) {
            $this->lpaCollection->shouldReceive('findOne')->andReturn(null);
        }

        if ($this->updateNumberModified === null) {
            $this->lpaCollection->shouldNotReceive('updateOne');
        } else {
            $updateResult = Mockery::mock(UpdateResult::class);
            $updateResult->shouldReceive('getModifiedCount')->andReturn($this->updateNumberModified);
            $this->lpaCollection->shouldReceive('updateOne')->once()->andReturn($updateResult);
        }

        return $resource;
    }

    /**
     * @return MockInterface
     */
    protected function getDefaultCursor()
    {
        $defaultCursor = Mockery::mock(Traversable::class);
        $defaultCursor->shouldReceive('toArray')->andReturn([]);
        return $defaultCursor;
    }

    /**
     * @return MockInterface
     */
    protected function getSingleCursor()
    {
        $singleCursor = Mockery::mock(Traversable::class);
        $singleCursor->shouldReceive('toArray')->andReturn([0]);
        return $singleCursor;
    }
}