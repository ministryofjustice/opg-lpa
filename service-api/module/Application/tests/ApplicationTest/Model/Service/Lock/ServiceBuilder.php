<?php

namespace ApplicationTest\Model\Service\Lock;

use Application\Model\Service\Lock\Service;
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
