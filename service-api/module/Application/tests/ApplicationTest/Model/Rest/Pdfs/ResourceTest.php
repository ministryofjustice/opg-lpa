<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Pdfs\Entity;
use Application\Model\Rest\Pdfs\Resource as PdfsResource;
use Application\Model\Rest\Pdfs\Resource;
use ApplicationTest\Model\AbstractResourceTest;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use Mockery;
use OpgTest\Lpa\DataModel\FixturesData;

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

    public function testFetchValidationFailed()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $validationError = $resource->fetch(-1);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('user', $validation));

        $resourceBuilder->verify();
    }

    public function testFetchLpa120NotAvailable()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $resource->fetch('lpa120');

        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals(new Entity([
            'type' => 'lpa120',
            'complete' => false,
            'status' => Entity::STATUS_NOT_AVAILABLE
        ], $lpa), $entity);
    }

    public function testFetchNotFound()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getPfLpa())->build();

        $entity = $resource->fetch(-1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document not found', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetchAllCheckAccess()
    {
        /** @var PdfsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetchAll();
    }
}