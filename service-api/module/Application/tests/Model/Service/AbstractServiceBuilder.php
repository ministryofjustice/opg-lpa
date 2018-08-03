<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Model\Service\AbstractService;
use Mockery;
use Mockery\MockInterface;
use MongoDB\UpdateResult;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\Logger;

abstract class AbstractServiceBuilder
{
    const LPA_COLLECTION_NAMESPACE = 'opglpa-api.lpa';

    /**
     * @var Lpa
     */
    protected $lpa;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var MockInterface|ApiLpaCollection
     */
    protected $apiLpaCollection = null;

    /**
     * @var int
     */
    protected $updateNumberModified = null;

    /**
     * @var array
     */
    protected $config = array();

    /**
     * @return AbstractService
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

    public function verify()
    {
        $this->apiLpaCollection->mockery_verify();
        Mockery::close();
    }

    protected function buildMocks(string $serviceName, $addDefaults = true)
    {
        /** @var MockInterface|Logger $loggerMock */
        $loggerMock = Mockery::mock(Logger::class);
        $loggerMock->shouldReceive('info');

        $this->apiLpaCollection = $this->apiLpaCollection ?: Mockery::mock(ApiLpaCollection::class);

        /** @var AbstractService $service */
        $service = new $serviceName($this->apiLpaCollection);
        $service->setLogger($loggerMock);

        if ($this->user !== null) {
            if ($this->lpa !== null) {
                $this->apiLpaCollection->shouldReceive('findOne')
                    ->withArgs([['_id' => (int)$this->lpa->getId(), 'user' => $this->user->getId()]])
                    ->andReturn($this->lpa->toArray(new DateCallback()));
                $this->apiLpaCollection->shouldReceive('findOne')
                    ->withArgs([['_id' => $this->lpa->getId()]])
                    ->andReturn($this->lpa->toArray(new DateCallback()));

                $this->apiLpaCollection->shouldReceive('count')
                    ->withArgs([[ '_id'=>$this->lpa->getId(), 'locked'=>true ], [ '_id'=>true ]])
                    ->andReturn(0);
            }
        }

        if ($this->lpa === null) {
            $this->apiLpaCollection->shouldNotReceive('findOne');
            $this->apiLpaCollection->shouldNotReceive('find');
        }

        if ($addDefaults) {
            $this->apiLpaCollection->shouldReceive('findOne')->andReturn(null);
        }

        if ($this->updateNumberModified === null) {
            $this->apiLpaCollection->shouldNotReceive('updateOne');
        } else {
            $updateResult = Mockery::mock(UpdateResult::class);
            $updateResult->shouldReceive('getModifiedCount')->andReturn($this->updateNumberModified);
            $this->apiLpaCollection->shouldReceive('updateOne')->once()->andReturn($updateResult);
        }

        return $service;
    }
}
