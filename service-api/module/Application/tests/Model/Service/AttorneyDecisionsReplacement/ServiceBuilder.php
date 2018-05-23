<?php

namespace ApplicationTest\Model\Service\AttorneyDecisionsReplacement;

use Application\Model\Service\AttorneyDecisionsReplacement\Service;
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
