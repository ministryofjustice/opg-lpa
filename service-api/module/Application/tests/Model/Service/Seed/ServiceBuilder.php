<?php

namespace ApplicationTest\Model\Service\Seed;

use Application\Model\Service\Seed\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $applicationsService = null;

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
}
