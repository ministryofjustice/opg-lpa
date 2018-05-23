<?php

namespace ApplicationTest\Model\Service\Seed;

use Application\Model\Service\Seed\Service;
use ApplicationTest\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
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
}