<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\Stats\StatsRepositoryInterface;
use Application\Model\DataAccess\Repository\Feedback\FeedbackRepositoryInterface;
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
     * @var MockInterface|ApplicationRepositoryInterface
     */
    private $applicationRepository = null;

    /**
     * @var MockInterface|FeedbackRepositoryInterface
     */
    private $feedbackRepository = null;


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

    public function withApplicationRepository($applicationRepository)
    {
        $this->applicationRepository = $applicationRepository;

        return $this;
    }

    public function withFeedbackRepository($feedbackRepository)
    {
        $this->feedbackRepository = $feedbackRepository;

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

        if ($this->applicationRepository !== null) {
            $service->setApplicationRepository($this->applicationRepository);
        }

        if ($this->feedbackRepository !== null) {
            $service->setFeedbackRepository($this->feedbackRepository);
        }

        return $service;
    }

    /**
     * Mockery verification function
     */
    public function verify()
    {
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

        if ($this->applicationRepository !== null) {
            $this->applicationRepository->mockery_verify();
        }

        if ($this->feedbackRepository !== null) {
            $this->feedbackRepository->mockery_verify();
        }

        Mockery::close();
    }
}
