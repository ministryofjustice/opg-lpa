<?php

namespace ApplicationTest\Model\Service\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Model\Service\Pdfs\Service;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Aws\Command;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Mockery;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;

class ServiceTest extends AbstractServiceTest
{
    private $config = [
        'pdf' => [
            'cache' => [
                's3' => [
                    'settings' => [
                        'Bucket' => null
                    ],
                    'client' => [
                        'version' => '2006-03-01',
                        'region' => 'eu-west-1',
                    ]
                ]
            ],
            'encryption' => [
                'keys' => [
                    'document' => 'teststringlongenoughtobevalid123'
                ],
                'options' => [
                    'algorithm' => 'aes',
                    'mode' => 'cbc'
                ]
            ],
            'queue' => [
                'sqs' => [
                    'settings' => [
                        'url' => 'https://testing',
                    ],
                    'client' => [
                        'region' => 'eu-west-1',
                        'version' => '2012-11-05',
                    ],
                ],
            ],
        ]
    ];

    public function testFetchNotFound()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $entity = $service->fetch($lpa->getId(), -1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->getStatus());
        $this->assertEquals('Document not found', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testFetchValidationFailed()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser(3);

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $validationError = $service->fetch($lpa->getId(), -1);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('user', $validation));

        $serviceBuilder->verify();
    }

    public function testFetchLpa120NotAvailable()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))->build();

        $data = $service->fetch($lpa->getId(), 'lpa120');

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

        $user = FixturesData::getUser();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->build();

        $data = $service->fetch($lpa->getId(), 'lp3');

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

        $user = FixturesData::getUser();

        $sqsClient = Mockery::mock(SqsClient::class);
        $sqsClient->shouldReceive('sendMessage')->once();

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withPdfConfig($this->config)
            ->withSqsClient($sqsClient)
            ->withS3Client($s3Client)
            ->build();

        $data = $service->fetch($lpa->getId(), 'lp1');

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

        $user = FixturesData::getUser();

        $sqsClient = Mockery::mock(SqsClient::class);

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('headObject')->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withPdfConfig($this->config)
            ->withSqsClient($sqsClient)
            ->withS3Client($s3Client)
            ->build();

        $data = $service->fetch($lpa->getId(), 'lp1');

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

        $user = FixturesData::getUser();

        $sqsClient = Mockery::mock(SqsClient::class);
        $sqsClient->shouldReceive('sendMessage')->once();

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withPdfConfig($this->config)
            ->withSqsClient($sqsClient)
            ->withS3Client($s3Client)
            ->build();

        $data = $service->fetch($lpa->getId(), 'lp1');

        $this->assertEquals([
            'type'     => 'lp1',
            'complete' => true,
            'status'   => Service::STATUS_IN_QUEUE
        ], $data);

        $serviceBuilder->verify();
    }
    
    public function testFetchLpa120PdfNotAvailable()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('getObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withPdfConfig($this->config)
            ->withS3Client($s3Client)
            ->build();

        $entity = $service->fetch($lpa->getId(), 'lpa120.pdf');

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->getStatus());
        $this->assertEquals('Document not found', $entity->getDetail());

        $serviceBuilder->verify();
    }

    public function testFetchLp1PdfCryptInvalidArgumentException()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $config = $this->config;
        $config['pdf']['encryption']['keys']['document'] = 'Invalid';

        $s3Client = Mockery::mock(S3Client::class);
        $s3ResultBody = Mockery::mock();
        $s3ResultBody->shouldReceive('getContents')->once();
        $s3Result = new Result();
        $s3Result['Body'] = $s3ResultBody;
        $s3Client->shouldReceive('getObject')->andReturn($s3Result)->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withPdfConfig($config)
            ->withS3Client($s3Client)
            ->build();

        $this->expectException(CryptInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encryption key');
        $service->fetch($lpa->getId(), 'lp1.pdf');

        $serviceBuilder->verify();
    }

    public function testFetchLp1Pdf()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $encryptionConfig = $this->config['pdf']['encryption'];
        $blockCipher = BlockCipher::factory('openssl', $encryptionConfig['options']);
        $blockCipher->setKey($encryptionConfig['keys']['document']);
        $blockCipher->setBinaryOutput(true);
        $encryptedData = $blockCipher->encrypt('test');

        $s3Client = Mockery::mock(S3Client::class);
        $s3ResultBody = Mockery::mock();
        $s3ResultBody->shouldReceive('getContents')->andReturn($encryptedData)->once();
        $s3Result = new Result();
        $s3Result['Body'] = $s3ResultBody;
        $s3Client->shouldReceive('getObject')->andReturn($s3Result)->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->getApplicationRepository($lpa, $user))
            ->withPdfConfig($this->config)
            ->withS3Client($s3Client)
            ->build();

        $fileResponse = $service->fetch($lpa->getId(), 'lp1.pdf');

        $this->assertTrue($fileResponse instanceof FileResponse);

        $serviceBuilder->verify();
    }
}
