<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use ApplicationTest\Model\Rest\Pdfs\TestableResource as PdfsResource;
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
        $resource = new PdfsResource();
        parent::buildMocks($resource);

        if ($this->dynamoQueueClient !== null) {
            $this->serviceLocatorMock->shouldReceive('get')->with('DynamoQueueClient')->andReturn($this->dynamoQueueClient);
        }

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
