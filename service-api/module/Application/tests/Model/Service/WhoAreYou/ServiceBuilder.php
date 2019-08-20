<?php

namespace ApplicationTest\Model\Service\WhoAreYou;

use Application\Model\Service\WhoAreYou\Service;
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
