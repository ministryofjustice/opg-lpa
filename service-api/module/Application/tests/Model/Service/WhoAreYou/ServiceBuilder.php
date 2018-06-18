<?php

namespace ApplicationTest\Model\Service\WhoAreYou;

use Application\Model\Service\WhoAreYou\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $apiWhoCollection = null;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->apiWhoCollection !== null) {
            $service->setApiWhoCollection($this->apiWhoCollection);
        }

        return $service;
    }

    public function withApiWhoCollection($apiWhoCollection)
    {
        $this->apiWhoCollection = $apiWhoCollection;
        return $this;
    }
}
