<?php

namespace ApplicationTest\Model\Service\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Model\Service\Pdfs\Service;
use ApplicationTest\AbstractServiceTest;
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

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var Service
     */
    private $service;

    private $config = array();

    public function setUp()
    {
        parent::setUp();

        $this->service = new Service(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->service->setLogger($this->logger);

        $this->service->setAuthorizationService($this->authorizationService);

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

        $this->service->setPdfConfig($this->config);
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->service);

        $this->service->fetch(-1);
    }

    public function testFetchNotFound()
    {
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa(FixturesData::getPfLpa())->build();

        $entity = $service->fetch(-1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document not found', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testFetchValidationFailed()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->user = 3;
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $validationError = $service->fetch(-1);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('user', $validation));

        $serviceBuilder->verify();
    }

    public function testFetchLpa120NotAvailable()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $data = $service->fetch('lpa120');

        $this->assertEquals([
            'type'     => 'lpa120',
            'complete' => false,
            'status'   => Service::STATUS_NOT_AVAILABLE
        ], $data);

        $serviceBuilder->verify();
    }

    public function testFetchLp3NotAvailable()
    {
        $lpa = FixturesData::getPfLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $data = $service->fetch('lp3');

        $this->assertEquals([
            'type'     => 'lp3',
            'complete' => false,
            'status'   => Service::STATUS_NOT_AVAILABLE
        ], $data);

        $serviceBuilder->verify();
    }

    public function testFetchLp1InQueue()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('checkStatus')->andReturn(DynamoQueueJob::STATE_WAITING)->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $service->setS3Client($mockS3Client);

        $data = $service->fetch('lp1');

        $this->assertEquals([
            'type'     => 'lp1',
            'complete' => true,
            'status'   => Service::STATUS_IN_QUEUE
        ], $data);

        $serviceBuilder->verify();
    }

    public function testFetchLp1Ready()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('deleteJob')->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->once();
        $service->setS3Client($mockS3Client);

        $data = $service->fetch('lp1');

        $this->assertEquals([
            'type'     => 'lp1',
            'complete' => true,
            'status'   => Service::STATUS_READY
        ], $data);

        $serviceBuilder->verify();
    }

    public function testFetchLp1NotQueued()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('checkStatus')->andReturn(DynamoQueueJob::STATE_DONE)->once();
        $dynamoQueueClient->shouldReceive('deleteJob')->once();
        $dynamoQueueClient->shouldReceive('enqueue')->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($this->config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $service->setS3Client($mockS3Client);

        $data = $service->fetch('lp1');

        $this->assertEquals([
            'type'     => 'lp1',
            'complete' => true,
            'status'   => Service::STATUS_IN_QUEUE
        ], $data);

        $serviceBuilder->verify();
    }

    public function testFetchLp1CryptInvalidArgumentException()
    {
        $lpa = FixturesData::getHwLpa();
        $dynamoQueueClient = Mockery::mock(DynamoQueue::class);
        $dynamoQueueClient->shouldReceive('checkStatus')->andReturn(DynamoQueueJob::STATE_DONE)->once();
        $dynamoQueueClient->shouldReceive('deleteJob')->once();
        $serviceBuilder = new ServiceBuilder();
        $config = $this->config;
        $config['pdf']['encryption']['keys']['queue'] = 'Invalid';
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withConfig($config)
            ->withDynamoQueueClient($dynamoQueueClient)
            ->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $service->setS3Client($mockS3Client);

        $this->expectException(CryptInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encryption key');
        $service->fetch('lp1');

        $serviceBuilder->verify();
    }

    public function testFetchLpa120PdfNotAvailable()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->withConfig($this->config)->build();

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('getObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();
        $service->setS3Client($mockS3Client);

        $entity = $service->fetch('lpa120.pdf');

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document not found', $entity->detail);

        $serviceBuilder->verify();
    }

    public function testFetchLp1PdfCryptInvalidArgumentException()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $config = $this->config;
        $config['pdf']['encryption']['keys']['document'] = 'Invalid';
        $service = $serviceBuilder
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
        $service->setS3Client($mockS3Client);

        $this->expectException(CryptInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encryption key');
        $service->fetch('lp1.pdf');

        $serviceBuilder->verify();
    }

    public function testFetchLp1Pdf()
    {
        $lpa = FixturesData::getHwLpa();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
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
        $service->setS3Client($mockS3Client);

        $fileResponse = $service->fetch('lp1.pdf');

        $this->assertTrue($fileResponse instanceof FileResponse);

        $serviceBuilder->verify();
    }
}
