<?php

namespace ApplicationTest\Model\Rest\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Pdfs\Collection;
use Application\Model\Rest\Pdfs\Entity;
use Application\Model\Rest\Pdfs\Resource as PdfsResource;
use Application\Model\Rest\Pdfs\Resource;
use ApplicationTest\Model\AbstractResourceTest;
use Aws\Command;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use DynamoQueue\Queue\Job\Job as DynamoQueueJob;
use Mockery;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;
use GuzzleHttp\Stream\StreamInterface as GuzzleStreamInterface;
use Application\Library\Http\Response\File as FileResponse;

class ResourceTest extends AbstractResourceTest
{
    private $config = array();

    public function setUp()
    {
        parent::setUp();

        $this->config = [
            'pdf' => [
                'cache' => [
                    's3' => [
                        'settings' => [
                            'Bucket' => null
                        ],
                        'client' => [
                            'version' => '2006-03-01',
                            'region' => 'eu-west-1',
                            'credentials' => null
                        ]
                    ]
                ],
                'encryption' => [
                    'keys' => [
                        'queue' => 'teststringlongenoughtobevalid123',
                        'document' => 'teststringlongenoughtobevalid123'
                    ],
                    'options' => [
                        'algorithm' => 'aes',
                        'mode' => 'cbc'
                    ]
                ]
            ]
        ];
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

        $resourceBuilder->verify();
    }

    public function testFetchLp3NotAvailable()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $resource->fetch('lp3');

        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals(new Entity([
            'type' => 'lp3',
            'complete' => false,
            'status' => Entity::STATUS_NOT_AVAILABLE
        ], $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchLp1InQueue()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('checkStatus')->andReturn(DynamoQueueJob::STATE_WAITING)->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $resource->setS3Client($mockS3Client);

        $entity = $resource->fetch('lp1');

        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals(new Entity([
            'type' => 'lp1',
            'complete' => true,
            'status' => Entity::STATUS_IN_QUEUE
        ], $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchLp1Ready()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('deleteJob')->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->once();
        $resource->setS3Client($mockS3Client);

        $entity = $resource->fetch('lp1');

        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals(new Entity([
            'type' => 'lp1',
            'complete' => true,
            'status' => Entity::STATUS_READY
        ], $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchLp1NotQueued()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('checkStatus')->andReturn(DynamoQueueJob::STATE_DONE)->once();
        $dynamoQueueClient->shouldReceive('deleteJob')->once();
        $dynamoQueueClient->shouldReceive('enqueue')->once();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $resource->setS3Client($mockS3Client);

        $entity = $resource->fetch('lp1');

        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals(new Entity([
            'type' => 'lp1',
            'complete' => true,
            'status' => Entity::STATUS_IN_QUEUE
        ], $lpa), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchLp1CryptInvalidArgumentException()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('checkStatus')->andReturn(DynamoQueueJob::STATE_DONE)->once();
        $dynamoQueueClient->shouldReceive('deleteJob')->once();
        $resourceBuilder = new ResourceBuilder();
        $config = $this->config;
        $config['pdf']['encryption']['keys']['queue'] = 'Invalid';
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $resource->setS3Client($mockS3Client);

        $this->setExpectedException(CryptInvalidArgumentException::class, 'Invalid encryption key');
        $resource->fetch('lp1');

        $resourceBuilder->verify();
    }

    public function testFetchLpa120PdfNotAvailable()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->withConfig($this->config)->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('getObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $resource->setS3Client($mockS3Client);

        $entity = $resource->fetch('lpa120.pdf');

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document not found', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetchLp1PdfCryptInvalidArgumentException()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $config = $this->config;
        $config['pdf']['encryption']['keys']['document'] = 'Invalid';
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($config)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $s3ResultBody = Mockery::mock(GuzzleStreamInterface::class);
        $s3ResultBody->shouldReceive('getContents')->once();
        $s3Result = new Result();
        $s3Result['Body'] = $s3ResultBody;
        $mockS3Client->shouldReceive('getObject')->andReturn($s3Result)->once();
        $resource->setS3Client($mockS3Client);

        $this->setExpectedException(CryptInvalidArgumentException::class, 'Invalid encryption key');
        $resource->fetch('lp1.pdf');

        $resourceBuilder->verify();
    }

    public function testFetchLp1Pdf()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->build();

        $config = $this->config['pdf']['encryption'];
        $blockCipher = BlockCipher::factory('mcrypt', $config['options']);
        $blockCipher->setKey($config['keys']['document']);
        $blockCipher->setBinaryOutput(true);
        $encryptedData = $blockCipher->encrypt('test');

        $mockS3Client = Mockery::mock(S3Client::class);
        $s3ResultBody = Mockery::mock(GuzzleStreamInterface::class);
        $s3ResultBody->shouldReceive('getContents')->andReturn($encryptedData)->once();
        $s3Result = new Result();
        $s3Result['Body'] = $s3ResultBody;
        $mockS3Client->shouldReceive('getObject')->andReturn($s3Result)->once();
        $resource->setS3Client($mockS3Client);

        $fileResponse = $resource->fetch('lp1.pdf');

        $this->assertTrue($fileResponse instanceof FileResponse);

        $resourceBuilder->verify();
    }

    public function testFetchAllCheckAccess()
    {
        /** @var PdfsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetchAll();
    }

    public function testFetchAll()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('deleteJob')->twice();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->twice();
        $resource->setS3Client($mockS3Client);

        $collection = $resource->fetchAll();

        $this->assertTrue($collection instanceof Collection);
        $array = $collection->toArray();
        $this->assertEquals(3, $array['count']);
        $this->assertEquals(3, $array['total']);
        $this->assertEquals(1, $array['pages']);
        $items = $array['items'];
        $this->assertArrayHasKey('lpa120', $items);
        $this->assertArrayHasKey('lp1', $items);
        $this->assertArrayHasKey('lp3', $items);

        $resourceBuilder->verify();
    }
}