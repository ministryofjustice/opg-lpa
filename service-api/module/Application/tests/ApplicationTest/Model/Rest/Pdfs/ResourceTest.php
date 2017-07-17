<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Pdfs\Resource as PdfsResource;
use Application\Model\Rest\Pdfs\Resource;
use ApplicationTest\Model\AbstractResourceTest;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use Mockery;

class ResourceTest extends AbstractResourceTest
{
    private $config = array();

    public function setUp()
    {
        parent::setUp();

        $this->config = ['pdf' => ['cache' => ['s3' => ['client' => [
            'version' => '2006-03-01',
            'region' => 'eu-west-1',
            'credentials' => null,
        ]]]]];
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
    }

    public function testGetPdfTypes()
    {
        $resource = new Resource();
        $this->assertEquals(['lpa120', 'lp3', 'lp1'], $resource->getPdfTypes());
    }

    public function testGetS3Client()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withConfig($this->config)->build();

        //Lazy loading so first call will retrieve the client, second will return the same instance
        $s3Client = $resource->testableGetS3Client();
        $loadedS3Client = $resource->testableGetS3Client();

        $this->assertTrue($s3Client instanceof S3Client);
        $this->assertTrue($s3Client === $loadedS3Client);
    }

    public function testGetDynamoQueueClient()
    {
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withDynamoQueueClient($dynamoQueueClient)->build();

        //Lazy loading so first call will retrieve the client, second will return the same instance
        $dynamoQueueClient = $resource->testableGetDynamoQueueClient();
        $loadedDynamoQueueClient = $resource->testableGetDynamoQueueClient();

        $this->assertTrue($dynamoQueueClient instanceof DynamoQueue);
        $this->assertTrue($dynamoQueueClient === $loadedDynamoQueueClient);
    }

    public function testFetchCheckAccess()
    {
        /** @var PdfsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch(-1);
    }

    public function testFetchAllCheckAccess()
    {
        /** @var PdfsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetchAll();
    }
}