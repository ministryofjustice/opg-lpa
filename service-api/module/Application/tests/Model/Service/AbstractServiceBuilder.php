<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Model\Service\AbstractService;
use Mockery;
use Mockery\MockInterface;
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
                $this->apiLpaCollection->shouldReceive('getById')
                    ->withArgs([(int)$this->lpa->getId(), $this->user->getId()])
                    ->andReturn($this->lpa->toArray(new DateCallback()));
                $this->apiLpaCollection->shouldReceive('getById')
                    ->withArgs([$this->lpa->getId()])
                    ->andReturn($this->lpa->toArray(new DateCallback()));
            }
        }

        if ($this->lpa === null) {
            $this->apiLpaCollection->shouldNotReceive('getById');
            $this->apiLpaCollection->shouldNotReceive('fetch');
        }

        if ($addDefaults) {
            $this->apiLpaCollection->shouldReceive('getById')->andReturn(null);
        }

        if ($this->updateNumberModified === null) {
            $this->apiLpaCollection->shouldNotReceive('update');
        } else {
            $this->apiLpaCollection->shouldReceive('update');
        }

        return $service;
    }
}
