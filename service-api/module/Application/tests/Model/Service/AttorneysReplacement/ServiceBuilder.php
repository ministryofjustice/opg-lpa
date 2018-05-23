<?php

namespace ApplicationTest\Model\Service\AttorneysReplacement;

use Application\Model\Service\AttorneysReplacement\Service;
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