<?php

namespace ApplicationTest\Model\Service\Password;

use Application\Model\Service\Password\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $authenticationService = null;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->authenticationService !== null) {
            $service->setAuthenticationService($this->authenticationService);
        }

        return $service;
    }

    /**
     * @param $authenticationService
     * @return $this
     */
    public function withAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;
        return $this;
    }
}
