<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiUserCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiWhoCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\Service\AbstractService;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;

abstract class AbstractServiceBuilder
{
    /**
     * @var MockInterface|Logger
     */
    private $logger = null;

    /**
     * @var MockInterface|ApiLpaCollection
     */
    private $apiLpaCollection = null;

    /**
     * @var MockInterface|ApiStatsLpasCollection
     */
    private $apiStatsLpasCollection = null;

    /**
     * @var MockInterface|ApiUserCollection
     */
    private $apiUserCollection = null;

    /**
     * @var MockInterface|ApiWhoCollection
     */
    private $apiWhoCollection = null;

    /**
     * @var MockInterface|AuthLogCollection
     */
    private $authLogCollection = null;

    /**
     * @var MockInterface|AuthUserCollection
     */
    private $authUserCollection = null;

    /**
     * @var MockInterface|LogRepositoryInterface
     */
    private $authLogRepository = null;

    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository = null;

    /**
     * @param $logger
     * @return $this
     */
    public function withLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param $apiLpaCollection
     * @return $this
     */
    public function withApiLpaCollection($apiLpaCollection)
    {
        $this->apiLpaCollection = $apiLpaCollection;

        return $this;
    }

    /**
     * @param $apiStatsLpasCollection
     * @return $this
     */
    public function withApiStatsLpasCollection($apiStatsLpasCollection)
    {
        $this->apiStatsLpasCollection = $apiStatsLpasCollection;

        return $this;
    }

    /**
     * @param $apiUserCollection
     * @return $this
     */
    public function withApiUserCollection($apiUserCollection)
    {
        $this->apiUserCollection = $apiUserCollection;

        return $this;
    }

    /**
     * @param $apiWhoCollection
     * @return $this
     */
    public function withApiWhoCollection($apiWhoCollection)
    {
        $this->apiWhoCollection = $apiWhoCollection;

        return $this;
    }

    /**
     * @param $authLogCollection
     * @return $this
     */
    public function withAuthLogCollection($authLogCollection)
    {
        $this->authLogCollection = $authLogCollection;

        return $this;
    }

    /**
     * @param $authUserCollection
     * @return $this
     */
    public function withAuthUserCollection($authUserCollection)
    {
        $this->authUserCollection = $authUserCollection;

        return $this;
    }

    /**
     * @param $authLogRepository
     * @return $this
     */
    public function withAuthLogRepository($authLogRepository)
    {
        $this->authLogRepository = $authLogRepository;

        return $this;
    }

    /**
     * @param $authUserRepository
     * @return $this
     */
    public function withAuthUserRepository($authUserRepository)
    {
        $this->authUserRepository = $authUserRepository;

        return $this;
    }

    /**
     * @return AbstractService
     */
    abstract public function build();

    /**
     * @param string $serviceName
     * @return AbstractService
     */
    protected function buildMocks(string $serviceName)
    {
        //  If the logger hasn't been mocked yet, do that now
        if ($this->logger === null) {
            $this->logger = Mockery::mock(Logger::class);
            $this->logger->shouldReceive('info');
        }

        /** @var AbstractService $service */
        $service = new $serviceName();

        $service->setLogger($this->logger);

        //  Add the collections if they are present
        if ($this->apiLpaCollection !== null) {
            $service->setApiLpaCollection($this->apiLpaCollection);
        }

        if ($this->apiStatsLpasCollection !== null) {
            $service->setApiStatsLpasCollection($this->apiStatsLpasCollection);
        }

        if ($this->apiUserCollection !== null) {
            $service->setApiUserCollection($this->apiUserCollection);
        }

        if ($this->apiWhoCollection !== null) {
            $service->setApiWhoCollection($this->apiWhoCollection);
        }

        if ($this->authLogRepository !== null) {
            $service->setLogRepository($this->authLogRepository);
        }

        if ($this->authUserRepository !== null) {
            $service->setUserRepository($this->authUserRepository);
        }

        return $service;
    }

    /**
     * Mockery verification function
     */
    public function verify()
    {
        if ($this->apiLpaCollection !== null) {
            $this->apiLpaCollection->mockery_verify();
        }

        if ($this->apiStatsLpasCollection !== null) {
            $this->apiStatsLpasCollection->mockery_verify();
        }

        if ($this->apiUserCollection !== null) {
            $this->apiUserCollection->mockery_verify();
        }

        if ($this->apiWhoCollection !== null) {
            $this->apiWhoCollection->mockery_verify();
        }

        if ($this->authLogCollection !== null) {
            $this->authLogCollection->mockery_verify();
        }

        if ($this->authUserCollection !== null) {
            $this->authUserCollection->mockery_verify();
        }

        if ($this->authLogRepository !== null) {
            $this->authLogRepository->mockery_verify();
        }

        if ($this->authUserRepository !== null) {
            $this->authUserRepository->mockery_verify();
        }

        Mockery::close();
    }
}
