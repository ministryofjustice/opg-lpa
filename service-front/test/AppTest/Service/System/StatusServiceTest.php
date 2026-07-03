<?php

declare(strict_types=1);

namespace AppTest\Service\System;

use App\Service\AddressLookup\OrdnanceSurvey;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Service\Redis\RedisClient;
use App\Service\System\StatusService;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use Exception;
use MakeShared\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SessionHandlerInterface;

use function json_encode;
use function time;

class StatusServiceTest extends TestCase
{
    private ApiClient&MockObject $apiClient;
    private DynamoDbClient&MockObject $dynamoDbClient;
    private SessionHandlerInterface&MockObject $sessionSaveHandler;
    private MailTransportInterface&MockObject $mailTransport;
    private OrdnanceSurvey&MockObject $ordnanceSurveyClient;
    private RedisClient&MockObject $redisClient;
    private array $config;

    protected function setUp(): void
    {
        $this->apiClient            = $this->createMock(ApiClient::class);
        $this->dynamoDbClient       = $this->createMock(DynamoDbClient::class);
        $this->sessionSaveHandler   = $this->createMock(SessionHandlerInterface::class);
        $this->mailTransport        = $this->createMock(MailTransportInterface::class);
        $this->ordnanceSurveyClient = $this->createMock(OrdnanceSurvey::class);
        $this->redisClient          = $this->createMock(RedisClient::class);
        $this->config               = [
            'admin' => [
                'dynamodb' => [
                    'settings' => [
                        'table_name' => 'status-table',
                    ],
                ],
            ],
            'redis' => [
                'ordnance_survey' => [
                    'max_call_per_min' => 6,
                ],
            ],
        ];
    }

    public function testConstructInstantiatesService(): void
    {
        $service = $this->createService();

        $this->assertInstanceOf(StatusService::class, $service);
    }

    public function testCheckReturnsWarnWhenOptionalServiceIsUnavailable(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->with('/ping')
            ->willReturn([
                'ok'      => true,
                'status'  => Constants::STATUS_PASS,
                'version' => '1.2.3',
            ]);
        $this->sessionSaveHandler->expects($this->once())
            ->method('open')
            ->with('', '')
            ->willReturn(true);
        $this->mailTransport->expects($this->once())
            ->method('healthcheck')
            ->willReturn([
                'ok'     => true,
                'status' => Constants::STATUS_PASS,
            ]);

        $result = $this->createMock(Result::class);
        $result->method('toArray')->willReturn([
            '@metadata' => ['statusCode' => 200],
            'Table'     => ['TableStatus' => 'ACTIVE'],
        ]);
        $this->dynamoDbClient->expects($this->once())
            ->method('__call')
            ->with('describeTable', [['TableName' => 'status-table']])
            ->willReturn($result);

        $this->redisClient->expects($this->once())->method('open');
        $this->redisClient->expects($this->exactly(3))
            ->method('read')
            ->willReturnCallback(fn (string $key) => match ($key) {
                'os_last_call' => (string) time(),
                'os_last_status' => '',
                'os_last_details' => json_encode('cached failure'),
            });
        $this->redisClient->expects($this->once())->method('close');

        $result = $this->createService()->check();

        /** @var array<string, mixed> $result */
        $this->assertTrue($result['ok']);
        $this->assertSame(Constants::STATUS_WARN, $result['status']);
        $this->assertSame(Constants::STATUS_PASS, $result['api']['status']);
        $this->assertSame(Constants::STATUS_PASS, $result['dynamo']['status']);
        $this->assertFalse($result['ordnanceSurvey']['ok']);
        $this->assertTrue($result['ordnanceSurvey']['cached']);
    }

    public function testCheckApiReturnsPassResultOnSuccessfulPing(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->with('/ping')
            ->willReturn([
                'ok'      => true,
                'status'  => Constants::STATUS_PASS,
                'service' => 'api',
            ]);

        $result = $this->invokePrivate($this->createService(), 'checkApi');

        $this->assertSame([
            'ok'      => true,
            'status'  => Constants::STATUS_PASS,
            'details' => [
                'response_code' => 200,
                'service'       => 'api',
            ],
        ], $result);
    }

    public function testCheckApiReturnsFailResultOnException(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->with('/ping')
            ->willThrowException(new Exception('API unavailable'));

        $result = $this->invokePrivate($this->createService(), 'checkApi');

        $this->assertFalse($result['ok']);
        $this->assertSame(Constants::STATUS_FAIL, $result['status']);
        $this->assertSame(500, $result['details']['response_code']);
    }

    public function testCheckSessionReturnsPassWithoutHandlerAndFailWhenOpenFails(): void
    {
        $serviceWithoutHandler     = new StatusService($this->apiClient);
        $serviceWithFailingHandler = new StatusService(
            $this->apiClient,
            null,
            $this->sessionSaveHandler,
        );

        $this->sessionSaveHandler->expects($this->once())
            ->method('open')
            ->with('', '')
            ->willReturn(false);

        $this->assertSame(
            ['ok' => true, 'status' => Constants::STATUS_PASS],
            $this->invokePrivate($serviceWithoutHandler, 'checkSession')
        );
        $this->assertSame(
            ['ok' => false, 'status' => Constants::STATUS_FAIL],
            $this->invokePrivate($serviceWithFailingHandler, 'checkSession')
        );
    }

    public function testCheckMailReturnsTransportHealthcheckOrPassWhenMissing(): void
    {
        $this->mailTransport->expects($this->once())
            ->method('healthcheck')
            ->willReturn(['ok' => false, 'status' => Constants::STATUS_FAIL]);

        $serviceWithMail    = new StatusService(
            $this->apiClient,
            null,
            null,
            $this->mailTransport,
        );
        $serviceWithoutMail = new StatusService($this->apiClient);

        $this->assertSame(
            ['ok' => false, 'status' => Constants::STATUS_FAIL],
            $this->invokePrivate($serviceWithMail, 'checkMail')
        );
        $this->assertSame(
            ['ok' => true, 'status' => Constants::STATUS_PASS],
            $this->invokePrivate($serviceWithoutMail, 'checkMail')
        );
    }

    public function testCheckDynamoReturnsPassForActiveTable(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('toArray')->willReturn([
            '@metadata' => ['statusCode' => 200],
            'Table'     => ['TableStatus' => 'UPDATING'],
        ]);

        $this->dynamoDbClient->expects($this->once())
            ->method('__call')
            ->with('describeTable', [['TableName' => 'status-table']])
            ->willReturn($result);

        $this->assertSame(
            ['ok' => true, 'status' => Constants::STATUS_PASS],
            $this->invokePrivate($this->createService(), 'checkDynamo')
        );
    }

    public function testCheckDynamoReturnsFailWhenDescribeTableThrows(): void
    {
        $this->dynamoDbClient->expects($this->once())
            ->method('__call')
            ->with('describeTable', [['TableName' => 'status-table']])
            ->willThrowException(new Exception('Dynamo unavailable'));

        $this->assertSame(
            ['ok' => false, 'status' => Constants::STATUS_FAIL],
            $this->invokePrivate($this->createService(), 'checkDynamo')
        );
    }

    public function testCheckOrdnanceSurveyReturnsCachedResultWithinRateLimit(): void
    {
        $this->redisClient->expects($this->once())->method('open');
        $this->redisClient->expects($this->exactly(3))
            ->method('read')
            ->willReturnCallback(fn (string $key) => match ($key) {
                'os_last_call' => (string) time(),
                'os_last_status' => '1',
                'os_last_details' => json_encode(['postcode' => 'SW1A 1AA']),
            });
        $this->redisClient->expects($this->once())->method('close');
        $this->ordnanceSurveyClient->expects($this->never())->method('lookupPostcode');

        $result = $this->invokePrivate($this->createService(), 'checkOrdnanceSurvey');

        $this->assertTrue($result['ok']);
        $this->assertSame(Constants::STATUS_PASS, $result['status']);
        $this->assertTrue($result['cached']);
        $this->assertSame(['postcode' => 'SW1A 1AA'], $result['details']);
    }

    public function testCallOrdnanceSurveyCachesSuccessfulLookup(): void
    {
        $lookupResult = [
            [
                'line1'    => '10 Downing Street',
                'line2'    => 'Westminster',
                'line3'    => 'London',
                'postcode' => 'SW1A 1AA',
            ],
        ];

        $this->ordnanceSurveyClient->expects($this->once())
            ->method('lookupPostcode')
            ->with('SW1A 1AA')
            ->willReturn($lookupResult);
        $this->ordnanceSurveyClient->expects($this->once())
            ->method('verify')
            ->with($lookupResult)
            ->willReturn(true);
        $this->redisClient->expects($this->exactly(3))
            ->method('write')
            ->willReturnCallback(function (string $key, string $value): bool {
                return match ($key) {
                    'os_last_call' => $value === '123',
                    'os_last_status' => $value === '1',
                    'os_last_details' => $value === json_encode([
                        'line1'    => '10 Downing Street',
                        'line2'    => 'Westminster',
                        'line3'    => 'London',
                        'postcode' => 'SW1A 1AA',
                    ]),
                    default => false,
                };
            });
        $this->redisClient->expects($this->once())->method('close');

        $result = $this->invokePrivate($this->createService(), 'callOrdnanceSurvey', [123]);

        $this->assertSame([
            'ok'      => true,
            'status'  => Constants::STATUS_PASS,
            'cached'  => false,
            'details' => $lookupResult[0],
        ], $result);
    }

    public function testCallOrdnanceSurveyReturnsFailWhenLookupThrows(): void
    {
        $this->ordnanceSurveyClient->expects($this->once())
            ->method('lookupPostcode')
            ->with('SW1A 1AA')
            ->willThrowException(new Exception('OS unavailable'));
        $this->redisClient->expects($this->exactly(3))
            ->method('write')
            ->willReturnCallback(function (string $key, string $value): bool {
                return match ($key) {
                    'os_last_call' => $value === '321',
                    'os_last_status' => $value === '',
                    'os_last_details' => $value === json_encode(''),
                    default => false,
                };
            });
        $this->redisClient->expects($this->once())->method('close');

        $result = $this->invokePrivate($this->createService(), 'callOrdnanceSurvey', [321]);

        $this->assertSame([
            'ok'      => false,
            'status'  => Constants::STATUS_FAIL,
            'cached'  => false,
            'details' => '',
        ], $result);
    }

    private function createService(): StatusService
    {
        return new StatusService(
            $this->apiClient,
            $this->dynamoDbClient,
            $this->sessionSaveHandler,
            $this->mailTransport,
            $this->ordnanceSurveyClient,
            $this->redisClient,
            $this->config,
        );
    }

    private function invokePrivate(StatusService $service, string $methodName, array $arguments = []): mixed
    {
        $method = new ReflectionMethod($service, $methodName);

        return $method->invokeArgs($service, $arguments);
    }
}
