<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;
use Application\Model\DataAccess\Repository\Stats\StatsRepositoryInterface;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
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
     * @var MockInterface|WhoRepositoryInterface
     */
    private $whoRepository = null;

    /**
     * @var MockInterface|StatsRepositoryInterface
     */
    private $statsRepository = null;

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
     * @param $whoRepository
     * @return $this
     */
    public function withWhoRepository($whoRepository)
    {
        $this->whoRepository = $whoRepository;

        return $this;
    }

    /**
     * @param $statsRepository
     * @return $this
     */
    public function withStatsRepository($statsRepository)
    {
        $this->statsRepository = $statsRepository;

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

        if ($this->authLogRepository !== null) {
            $service->setLogRepository($this->authLogRepository);
        }

        if ($this->authUserRepository !== null) {
            $service->setUserRepository($this->authUserRepository);
        }

        if ($this->whoRepository !== null) {
            $service->setWhoRepository($this->whoRepository);
        }

        if ($this->statsRepository !== null) {
            $service->setStatsRepository($this->statsRepository);
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

        if ($this->whoRepository !== null) {
            $this->whoRepository->mockery_verify();
        }

        if ($this->statsRepository !== null) {
            $this->statsRepository->mockery_verify();
        }

        Mockery::close();
    }
}
