<?php

namespace ApplicationTest\Model\Service\AccountCleanup;

use Application\Model\Service\AccountCleanup\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;
use Mockery\MockInterface;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $config = null;

    private $notifyClient = null;

    private $usersService = null;

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

        if ($this->usersService !== null) {
            $service->setUsersService($this->usersService);
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
     * @param MockInterface $usersService
     * @return $this
     */
    public function withUsersService($usersService)
    {
        $this->usersService = $usersService;
        return $this;
    }
}
