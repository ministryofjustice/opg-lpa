<?php

namespace ApplicationTest\Model\Service\AccountCleanup;

use Application\Model\Service\AccountCleanup\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;
use Mockery\MockInterface;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $config = null;

    private $notifyClient = null;

    private $snsClient = null;

    private $userManagementService = null;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->config !== null) {
            $service->setConfig($this->config);
        }

        if ($this->notifyClient !== null) {
            $service->setNotifyClient($this->notifyClient);
        }

        if ($this->snsClient !== null) {
            $service->setSnsClient($this->snsClient);
        }

        if ($this->userManagementService !== null) {
            $service->setUserManagementService($this->userManagementService);
        }

        return $service;
    }

    /**
     * @param $config
     * @return $this
     */
    public function withConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param MockInterface $notifyClient
     * @return $this
     */
    public function withNotifyClient($notifyClient)
    {
        $this->notifyClient = $notifyClient;
        return $this;
    }

    /**
     * @param MockInterface $snsClient
     * @return $this
     */
    public function withSnsClient($snsClient)
    {
        $this->snsClient = $snsClient;
        return $this;
    }

    /**
     * @param MockInterface $userManagementService
     * @return $this
     */
    public function withUserManagementService($userManagementService)
    {
        $this->userManagementService = $userManagementService;
        return $this;
    }
}
