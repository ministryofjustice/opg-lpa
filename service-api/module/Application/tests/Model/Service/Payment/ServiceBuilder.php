<?php

namespace ApplicationTest\Model\Service\Payment;

use Application\Model\Service\Payment\Service;
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
