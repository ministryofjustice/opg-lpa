<?php

namespace ApplicationTest\Model\Service\Applications;

use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    /**
     * @return TestableService
     */
    public function build()
    {
        /** @var TestableService $service */
        $service = parent::buildMocks(TestableService::class);//, false);
        return $service;
    }
}
