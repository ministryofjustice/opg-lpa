<?php

namespace ApplicationTest\Model\Service\AttorneysPrimary;

use Application\Model\Service\AttorneysPrimary\Service;
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
        return $service;
    }
}
