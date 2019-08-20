<?php

namespace ApplicationTest\Model\Service\AttorneyDecisionsPrimary;

use Application\Model\Service\AttorneyDecisionsPrimary\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

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
