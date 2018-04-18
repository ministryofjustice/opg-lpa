<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\Pdfs\Resource as PdfsResource;
use ApplicationTest\AbstractResourceBuilder;
use Mockery\MockInterface;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $dynamoQueueClient = null;

    /**
     * @return PdfsResource
     */
    public function build()
    {
        /** @var PdfsResource $resource */
        $resource = parent::buildMocks(PdfsResource::class);

        if ($this->dynamoQueueClient !== null) {
            $resource->setDynamoQueueClient($this->dynamoQueueClient);
        }

        $resource->setPdfConfig($this->config);

        return $resource;
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
