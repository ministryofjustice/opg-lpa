<?php

namespace ApplicationTest\Model\Service\Pdfs;

use Application\Model\Service\Pdfs\Service;
use ApplicationTest\AbstractServiceBuilder;
use Mockery\MockInterface;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $dynamoQueueClient = null;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->dynamoQueueClient !== null) {
            $service->setDynamoQueueClient($this->dynamoQueueClient);
        }

        $service->setPdfConfig($this->config);

        return $service;
    }

    /**
     * @param MockInterface $dynamoQueueClient
     * @return $this
     */
    public function withDynamoQueueClient($dynamoQueueClient)
    {
        $this->dynamoQueueClient = $dynamoQueueClient;
        return $this;
    }
}
