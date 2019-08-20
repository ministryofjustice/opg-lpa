<?php

namespace ApplicationTest\Model\Service\Pdfs;

use Application\Model\Service\Pdfs\Service;
use ApplicationTest\Model\Service\AbstractServiceBuilder;
use Mockery\MockInterface;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $pdfConfig = null;

    private $s3Client = null;

    private $sqsClient = null;

    /**
     * @return Service
     */
    public function build()
    {
        /** @var Service $service */
        $service = parent::buildMocks(Service::class);

        if ($this->pdfConfig !== null) {
            $service->setPdfConfig($this->pdfConfig);
        }

        if ($this->s3Client !== null) {
            $service->setS3Client($this->s3Client);
        }

        if ($this->sqsClient !== null) {
            $service->setSqsClient($this->sqsClient);
        }

        return $service;
    }

    /**
     * @param $pdfConfig
     * @return $this
     */
    public function withPdfConfig($pdfConfig)
    {
        $this->pdfConfig = $pdfConfig;
        return $this;
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

    /**
     * @param MockInterface $sqsClient
     * @return $this
     */
    public function withSqsClient($sqsClient)
    {
        $this->sqsClient = $sqsClient;
        return $this;
    }

}
