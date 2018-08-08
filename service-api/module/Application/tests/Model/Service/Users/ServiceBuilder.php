<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Model\Service\Users\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    /**
     * @var null
     */
    private $applicationsService;

    /**
     * @var
     */
    private $userManagementService;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->applicationsService !== null) {
            $service->setApplicationsService($this->applicationsService);
        }

        if ($this->userManagementService !== null) {
            $service->setUserManagementService($this->userManagementService);
        }

        return $service;
    }

    /**
     * @param $applicationsService
     * @return $this
     */
    public function withApplicationsService($applicationsService)
    {
        $this->applicationsService = $applicationsService;
        return $this;
    }

    /**
     * @param $userManagementService
     * @return $this
     */
    public function withUserManagementService($userManagementService)
    {
        $this->userManagementService = $userManagementService;
        return $this;
    }
}
