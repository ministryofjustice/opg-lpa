<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Model\Service\Pdfs\Service;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use Aws\Command;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use MakeSharedTest\DataModel\FixturesData;
use Mockery;

final class ServiceTest extends AbstractServiceTestCase
{
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Service();
        $this->service->setLogger($this->logger);
    }

    private array $config = [
        'pdf' => [
            'docIdSuffix' => 'MrFoo',
            'cache' => [
                's3' => [
                    'settings' => [
                        'Bucket' => 's3bucketname'
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

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $entity = $this->service->fetch(strval($lpa->getId()), -1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Document not found',
            ],
            $entity->toArray()
        );
    }

    public function testFetchValidationFailed()
    {
        //The bad id value on this user will fail validation
        $lpa = FixturesData::getHwLpa();
        $lpa->setUser('3');

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $validationError = $this->service->fetch(strval($lpa->getId()), -1);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'user' => ['value' => '3', 'messages' => ['length-must-equal:32']],
                ]
            ],
            $validationError->toArray()
        );
    }

    public function testFetchLpa120NotAvailable()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $data = $this->service->fetch(strval($lpa->getId()), 'lpa120');

        $this->assertEquals([
            'type' => 'lpa120',
            'complete' => false,
            'status' => Service::STATUS_NOT_AVAILABLE
        ], $data);
    }

    public function testFetchLp3NotAvailable()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));

        $data = $this->service->fetch(strval($lpa->getId()), 'lp3');

        $this->assertEquals([
            'type' => 'lp3',
            'complete' => false,
            'status' => Service::STATUS_NOT_AVAILABLE
        ], $data);
    }

    public function testFetchLp1InQueue()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $sqsClient = Mockery::mock(SqsClient::class);
        $sqsClient->shouldReceive('sendMessage')->once();

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setPdfConfig($this->config);
        $this->service->setSqsClient($sqsClient);
        $this->service->setS3Client($s3Client);

        $this->logger->shouldReceive('error')
            ->once()
            ->with(
                'Exception while attempting to get PDF info from S3',
                Mockery::on(function (array $context) {
                    $this->assertSame('PDF_S3_HEAD_FAILED', $context['error_code']);
                    $this->assertArrayHasKey('exception', $context);
                    $this->assertInstanceOf(\Aws\S3\Exception\S3Exception::class, $context['exception']);

                    return true;
                })
            );

        $data = $this->service->fetch(strval($lpa->getId()), 'lp1');

        $this->assertEquals([
            'type' => 'lp1',
            'complete' => true,
            'status' => Service::STATUS_IN_QUEUE
        ], $data);
    }

    public function testFetchLp1Ready()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $sqsClient = Mockery::mock(SqsClient::class);

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('headObject')->once();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setPdfConfig($this->config);
        $this->service->setSqsClient($sqsClient);
        $this->service->setS3Client($s3Client);

        $data = $this->service->fetch(strval($lpa->getId()), 'lp1');

        $this->assertEquals([
            'type' => 'lp1',
            'complete' => true,
            'status' => Service::STATUS_READY
        ], $data);
    }

    public function testFetchLp1NotQueued()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $sqsClient = Mockery::mock(SqsClient::class);
        $sqsClient->shouldReceive('sendMessage')->once();

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('headObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setPdfConfig($this->config);
        $this->service->setSqsClient($sqsClient);
        $this->service->setS3Client($s3Client);

        $this->logger->shouldReceive('error')
            ->once()
            ->with(
                'Exception while attempting to get PDF info from S3',
                Mockery::on(function (array $context) {
                    $this->assertSame('PDF_S3_HEAD_FAILED', $context['error_code']);
                    $this->assertArrayHasKey('exception', $context);
                    $this->assertInstanceOf(\Aws\S3\Exception\S3Exception::class, $context['exception']);

                    return true;
                })
            );

        $data = $this->service->fetch(strval($lpa->getId()), 'lp1');

        $this->assertEquals([
            'type' => 'lp1',
            'complete' => true,
            'status' => Service::STATUS_IN_QUEUE
        ], $data);
    }

    public function testFetchLpa120PdfNotAvailable()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $s3Client = Mockery::mock(S3Client::class);
        $s3Client->shouldReceive('getObject')->andThrow(new S3Exception('Test', new Command('Test')))->once();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setPdfConfig($this->config);
        $this->service->setS3Client($s3Client);

        $entity = $this->service->fetch(strval($lpa->getId()), 'lpa120.pdf');

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(
            [
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Document not found',
            ],
            $entity->toArray()
        );
    }

    public function testFetchLp1Pdf()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $s3Client = Mockery::mock(S3Client::class);
        $s3ResultBody = Mockery::mock();
        $s3ResultBody->shouldReceive('getContents')->andReturn('<<pdf-file-contents>>')->once();
        $s3Result = new Result();
        $s3Result['Body'] = $s3ResultBody;

        $expectedKey = 'lp1-' . hash(
            'md5',
            $lpa->toJson() . $this->config['pdf']['docIdSuffix']
        );

        $expectedClientSettings = $this->config['pdf']['cache']['s3']['settings'] + ['Key' => $expectedKey];

        $s3Client->shouldReceive('getObject')
            ->with($expectedClientSettings)
            ->andReturn($s3Result)->once();

        $this->service->setApplicationRepository($this->getApplicationRepository($lpa, $user));
        $this->service->setPdfConfig($this->config);
        $this->service->setS3Client($s3Client);

        $fileResponse = $this->service->fetch(strval($lpa->getId()), 'lp1.pdf');

        $this->assertTrue($fileResponse instanceof FileResponse);
        $this->assertEquals('<<pdf-file-contents>>', $fileResponse->getContent());
    }
}
