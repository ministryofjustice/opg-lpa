<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Model\Service\Users\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $apiUserCollection = null;

    private $applicationsService = null;

    private $userManagementService;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->apiUserCollection !== null) {
            $service->setApiUserCollection($this->apiUserCollection);
        }

        if ($this->applicationsService !== null) {
            $service->setApplicationsService($this->applicationsService);
        }

        if ($this->userManagementService !== null) {
            $service->setUserManagementService($this->userManagementService);
        }

        return $service;
    }

    public function withApiUserCollection($apiUserCollection)
    {
        $this->apiUserCollection = $apiUserCollection;
        return $this;
    }

    public function withApplicationsService($applicationsService)
    {
        $this->applicationsService = $applicationsService;
        return $this;
    }

    public function withUserManagementService($userManagementService)
    {
        $this->userManagementService = $userManagementService;
        return $this;
    }
}
