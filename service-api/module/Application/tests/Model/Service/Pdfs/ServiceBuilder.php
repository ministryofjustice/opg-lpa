<?php

namespace ApplicationTest\Model\Service\Pdfs;

use Application\Model\Service\Pdfs\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;
use Mockery\MockInterface;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $dynamoQueueClient = null;

    private $s3Client = null;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        $service->setPdfConfig($this->config);

        if ($this->dynamoQueueClient !== null) {
            $service->setDynamoQueueClient($this->dynamoQueueClient);
        }

        if ($this->s3Client !== null) {
            $service->setS3Client($this->s3Client);
        }

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

    /**
     * @param MockInterface $s3Client
     * @return $this
     */
    public function withS3Client($s3Client)
    {
        $this->s3Client = $s3Client;
        return $this;
    }
}
