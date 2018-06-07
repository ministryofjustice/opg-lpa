<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Model\Service\Users\Service;
use ApplicationTest\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $authUserCollection = null;

    private $userManagementService;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class, true, $this->authUserCollection);

        if ($this->applicationsService !== null) {
            $service->setApplicationsService($this->applicationsService);
        }

        if ($this->userManagementService !== null) {
            $service->setUserManagementService($this->userManagementService);
        }

        return $service;
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

    public function withUserManagementService($userManagementService)
    {
        $this->userManagementService = $userManagementService;
        return $this;
    }
}
