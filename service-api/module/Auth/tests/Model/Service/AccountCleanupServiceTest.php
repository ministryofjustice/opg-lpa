<?php

namespace AuthTest\Model\Service;

use Auth\Model\Service\AccountCleanupService;
use Application\Model\DataAccess\Mongo\Collection\User;
use Auth\Model\Service\UserManagementService;
use Aws\Sns\SnsClient;
use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;
use Psr\Http\Message\RequestInterface;

class AccountCleanupServiceTest extends ServiceTestCase
{
    /**
     * @var AccountCleanupService
     */
    private $service;

    /**
     * @var MockInterface|UserManagementService
     */
    private $userManagementService;

    /**
     * @var MockInterface|Logger
     */
    private $logger;

    /**
     * @var MockInterface|SnsClient
     */
    private $snsClient;

    /**
     * @var MockInterface|GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var MockInterface|RequestInterface
     */
    private $request;

    /** @var array  */
    private $config = [
        'stack' => [
            'name' => 'unit_test'
        ],
        'cleanup' => [
            'notification' => [
                'token' => 'unit_test',
            ],
        ],
        'log' => [
            'sns' => [
                'endpoints' => [
                    'info' => 'info_endpoint',
                ],
                'client' => []
            ]
        ]
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->userManagementService = Mockery::mock(UserManagementService::class);

        $this->logger = Mockery::mock(Logger::class);

        $this->snsClient = Mockery::mock(SnsClient::class);

        $this->guzzleClient = Mockery::mock(GuzzleClient::class);

        $this->request = Mockery::mock(RequestInterface::class);

        $this->service = new AccountCleanupService($this->authUserCollection, $this->authLogCollection);

        $this->service->setUserManagementService($this->userManagementService);
        $this->service->setSnsClient($this->snsClient);
        $this->service->setGuzzleClient($this->guzzleClient);
        $this->service->setConfig($this->config);

        $this->service->setLogger($this->logger);
    }

    public function testCleanupNone()
    {
        $this->setAccountsExpectations();

        $this->snsClient->shouldReceive('publish')->withArgs(function ($message) {
            return $message['TopicArn'] === 'info_endpoint' && empty($message['Message']) === false
                && $message['Subject'] === 'LPA Account Cleanup Notification'
                && $message['MessageStructure'] === 'string';
        })->once();

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupNoneSnsPublishException()
    {
        $this->setAccountsExpectations();

        $this->snsClient->shouldReceive('publish')->once()
            ->andThrow(new Exception('Test exception'));

        $this->logger->shouldReceive('alert')->withArgs(function ($message, $extra) {
            return $message === 'Unable to send AWS SNS notification' && array_key_exists('exception', $extra);
        })->once();

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupExpiredAccounts()
    {
        $this->setAccountsExpectations([new User(['_id' => 1])]);

        $this->snsClient->shouldReceive('publish')->once();

        //  Create the expected unit test delete target including the user ID
        $apiDeleteTarget = 'http://unit_test_delete_target/' . 1;

        $this->guzzleClient->shouldReceive('delete')
            ->withArgs([$apiDeleteTarget, [
                'headers' => [
                    'AuthCleanUpToken' => 'unit_test',
                ],
            ]])->once();

        $this->userManagementService->shouldReceive('delete')->withArgs([1, 'expired']);

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupExpiredAccountsException()
    {
        $this->setAccountsExpectations([new User(['_id' => 1])]);

        $this->snsClient->shouldReceive('publish')->once();

        $this->guzzleClient->shouldReceive('delete')->once()
            ->andThrow(new GuzzleClientException('Unit test exception', $this->request));

        $this->userManagementService->shouldReceive('delete')->withArgs([1, 'expired']);

        $this->logger->shouldReceive('alert')->withArgs(function ($message, $extra) {
            return $message === 'Unable to clean up expired account on the API service'
                && $extra['exception'] === 'Unit test exception';
        })->once();

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupOneWeekWarningAccountsSuccessful()
    {
        $this->setAccountsExpectations([], [new User([
            '_id' => 1,
            'identity' => 'unit@test.com',
            'last_login' => new DateTime('-9 months +1 week')
        ])]);

        $this->snsClient->shouldReceive('publish')->once();

        $this->guzzleClient->shouldReceive('post')
            ->withArgs(function ($uri, $options) {
                return $uri === 'http://callback' && $options === [
                    'form_params' => [
                        'Type' => '1-week-notice',
                        'Username' => 'unit@test.com',
                        'Date' => ((new DateTime('-9 months +1 week'))
                            ->add(DateInterval::createFromDateString('+9 months')))->format('Y-m-d'),
                    ],
                    'headers' => [
                        'Token' => 'unit_test',
                    ],
                ];
            })->once();

        $this->authUserCollection->shouldReceive('setInactivityFlag')->withArgs([1, '1-week-notice'])->once();

        $result = $this->service->cleanup('http://callback');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupOneWeekWarningAccountsGuzzleException()
    {
        $this->setAccountsExpectations([], [new User([
            '_id' => 1,
            'identity' => 'unit@test.com',
            'last_login' => new DateTime('-9 months +1 week')
        ])]);

        $this->snsClient->shouldReceive('publish')->once();

        $this->guzzleClient->shouldReceive('post')->once()
            ->andThrow(new GuzzleClientException('Unit test exception', $this->request));

        $this->logger->shouldReceive('warn')->withArgs(function ($message, $extra) {
            return $message === 'Unable to send account expiry notification'
                && $extra['exception'] === 'Unit test exception';
        })->once();

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupOneWeekWarningAccountsException()
    {
        $this->setAccountsExpectations([], [new User([
            '_id' => 1,
            'identity' => 'unit@test.com',
            'last_login' => new DateTime('-9 months +1 week')
        ])]);

        $this->snsClient->shouldReceive('publish')->once();

        $this->guzzleClient->shouldReceive('post')->once()
            ->andThrow(new Exception('Unit test exception'));

        $this->logger->shouldReceive('alert')->withArgs(function ($message, $extra) {
            return $message === 'Unable to send account expiry notification'
                && $extra['exception'] === 'Unit test exception';
        })->once();

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupOneMonthWarningAccountsSuccessful()
    {
        $this->setAccountsExpectations([], [], [new User([
            '_id' => 1,
            'identity' => 'unit@test.com',
            'last_login' => new DateTime('-8 months')
        ])]);

        $this->snsClient->shouldReceive('publish')->once();

        $this->guzzleClient->shouldReceive('post')
            ->withArgs(function ($uri, $options) {
                return $uri === 'http://callback' && $options === [
                        'form_params' => [
                            'Type' => '1-month-notice',
                            'Username' => 'unit@test.com',
                            'Date' => ((new DateTime('-8 months'))
                                ->add(DateInterval::createFromDateString('+9 months')))->format('Y-m-d'),
                        ],
                        'headers' => [
                            'Token' => 'unit_test',
                        ],
                    ];
            })->once();

        $this->authUserCollection->shouldReceive('setInactivityFlag')->withArgs([1, '1-month-notice'])->once();

        $result = $this->service->cleanup('http://callback');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupUnactivatedAccounts()
    {
        $this->setAccountsExpectations([], [], [], [new User(['_id' => 1])]);

        $this->snsClient->shouldReceive('publish')->once();

        $this->userManagementService->shouldReceive('delete')->withArgs([1, 'unactivated']);

        $result = $this->service->cleanup('');

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    private function setAccountsExpectations(
        array $expiredAccounts = [],
        array $oneWeekWarningAccounts = [],
        array $oneMonthWarningAccounts = [],
        array $unactivatedAccounts = []
    ) {
        $this->authUserCollection->shouldReceive('getAccountsInactiveSince')
            ->withArgs(function ($lastLoginBefore) {
                return $lastLoginBefore < new DateTime('-9 months +1 week')
                    && $lastLoginBefore >= new DateTime('-9 months -1 second');
            })->once()
            ->andReturn($expiredAccounts);

        $this->authUserCollection->shouldReceive('getAccountsInactiveSince')
            ->withArgs(function ($lastLoginBefore, $excludeFlag = null) {
                return $lastLoginBefore < new DateTime('-8 months')
                    && $lastLoginBefore >= new DateTime('-9 months +1 week -1 second')
                    && $excludeFlag === '1-week-notice';
            })->once()
            ->andReturn($oneWeekWarningAccounts);

        $this->authUserCollection->shouldReceive('getAccountsInactiveSince')
            ->withArgs(function ($lastLoginBefore, $excludeFlag = null) {
                return $lastLoginBefore >= new DateTime('-8 months -1 second')
                    && $excludeFlag === '1-month-notice';
            })->once()
            ->andReturn($oneMonthWarningAccounts);

        $this->authUserCollection->shouldReceive('getAccountsUnactivatedOlderThan')
            ->withArgs(function ($olderThan) {
                return $olderThan >= new DateTime('-24 hours -1 second');
            })->once()
            ->andReturn($unactivatedAccounts);
    }
}
