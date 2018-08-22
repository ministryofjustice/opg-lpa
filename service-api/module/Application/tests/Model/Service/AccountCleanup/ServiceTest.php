<?php

namespace ApplicationTest\Model\Service\AccountCleanup;

use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiUserCollection;
use Application\Model\DataAccess\Mongo\Collection\User;
use Application\Model\Service\UserManagement\Service as UserManagementService;
use ApplicationTest\Model\Service\AbstractServiceTest;
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

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var MockInterface|ApiLpaCollection
     */
    private $apiLpaCollection;

    /**
     * @var MockInterface|ApiUserCollection
     */
    private $apiUserCollection;

    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository;

    /**
     * @var array
     */
    private $config = [
        'stack' => [
            'name' => 'unit_test'
        ],
        'cleanup' => [
            'notification' => [
                'token' => 'unit_test',
                'callback' => 'http://callback',
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

    /**
     * @var MockInterface|GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var MockInterface|SnsClient
     */
    private $snsClient;

    /**
     * @var MockInterface|UserManagementService
     */
    private $userManagementService;

    /**
     * @var MockInterface|Logger
     */
    private $logger;

    protected function setUp()
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->apiLpaCollection = Mockery::mock(ApiLpaCollection::class);

        $this->apiUserCollection = Mockery::mock(ApiUserCollection::class);

        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->guzzleClient = Mockery::mock(GuzzleClient::class);

        $this->snsClient = Mockery::mock(SnsClient::class);

        $this->userManagementService = Mockery::mock(UserManagementService::class);

        $this->logger = Mockery::mock(Logger::class);
    }

    public function testCleanupNone()
    {
        $this->setAccountsExpectations();

        $this->snsClient->shouldReceive('publish')
            ->withArgs(function ($message) {
                return $message['TopicArn'] === 'info_endpoint' && empty($message['Message']) === false
                    && $message['Subject'] === 'LPA Account Cleanup Notification'
                    && $message['MessageStructure'] === 'string';
            })
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

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

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupExpiredAccounts()
    {
        $this->setAccountsExpectations([new User(['_id' => 1])]);

        $this->snsClient->shouldReceive('publish')
            ->once();

        $this->userManagementService->shouldReceive('delete')
            ->withArgs([1, 'expired']);

        $this->apiLpaCollection->shouldReceive('fetchByUserId')
            ->with(1)
            ->andReturn(new \ArrayIterator([]));

        $this->apiUserCollection->shouldReceive('deleteById')
            ->withArgs([1])
            ->andReturnNull();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupExpiredAccountsException()
    {
        $this->setAccountsExpectations([new User(['_id' => 1])]);

        $this->snsClient->shouldReceive('publish')
            ->once();

        $this->userManagementService->shouldReceive('delete')
            ->withArgs([1, 'expired']);

        $this->apiLpaCollection->shouldReceive('fetchByUserId')
            ->with(1)
            ->andReturn(new \ArrayIterator([]));

        $this->apiUserCollection->shouldReceive('deleteById')
            ->withArgs([1])
            ->andReturnNull();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

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
            })
            ->once();

        $this->authUserRepository->shouldReceive('setInactivityFlag')
            ->withArgs([1, '1-week-notice'])
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

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

        $this->snsClient->shouldReceive('publish')
            ->once();

        /** @var RequestInterface $request */
        $request = Mockery::mock(RequestInterface::class);

        $this->guzzleClient->shouldReceive('post')
            ->once()
            ->andThrow(new GuzzleClientException('Unit test exception', $request));

        $this->logger->shouldReceive('warn')
            ->withArgs(function ($message, $extra) {
                return $message === 'Unable to send account expiry notification'
                    && $extra['exception'] === 'Unit test exception';
            })
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

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

        $this->snsClient->shouldReceive('publish')
            ->once();

        $this->guzzleClient->shouldReceive('post')
            ->once()
            ->andThrow(new Exception('Unit test exception'));

        $this->logger->shouldReceive('alert')
            ->withArgs(function ($message, $extra) {
                return $message === 'Unable to send account expiry notification'
                    && $extra['exception'] === 'Unit test exception';
            })
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

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

        $this->snsClient->shouldReceive('publish')
            ->once();

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
            })
            ->once();

        $this->authUserRepository->shouldReceive('setInactivityFlag')
            ->withArgs([1, '1-month-notice'])
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupUnactivatedAccounts()
    {
        $this->setAccountsExpectations([], [], [], [new User(['_id' => 1])]);

        $this->snsClient->shouldReceive('publish')
            ->once();

        $this->userManagementService->shouldReceive('delete')
            ->withArgs([1, 'unactivated']);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($this->apiLpaCollection)
            ->withAuthUserRepository($this->authUserRepository)
            ->withConfig($this->config)
            ->withGuzzleClient($this->guzzleClient)
            ->withSnsClient($this->snsClient)
            ->withUserManagementService($this->userManagementService)
            ->withLogger($this->logger)
            ->build();

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    private function setAccountsExpectations(array $expiredAccounts = [], array $oneWeekWarningAccounts = [], array $oneMonthWarningAccounts = [], array $unactivatedAccounts = [])
    {
        $this->authUserRepository->shouldReceive('getAccountsInactiveSince')
            ->withArgs(function ($lastLoginBefore) {
                return $lastLoginBefore < new DateTime('-9 months +1 week')
                    && $lastLoginBefore >= new DateTime('-9 months -1 second');
            })
            ->once()
            ->andReturn($expiredAccounts);

        $this->authUserRepository->shouldReceive('getAccountsInactiveSince')
            ->withArgs(function ($lastLoginBefore, $excludeFlag = null) {
                return $lastLoginBefore < new DateTime('-8 months')
                    && $lastLoginBefore >= new DateTime('-9 months +1 week -1 second')
                    && $excludeFlag === '1-week-notice';
            })
            ->once()
            ->andReturn($oneWeekWarningAccounts);

        $this->authUserRepository->shouldReceive('getAccountsInactiveSince')
            ->withArgs(function ($lastLoginBefore, $excludeFlag = null) {
                return $lastLoginBefore >= new DateTime('-8 months -1 second')
                    && $excludeFlag === '1-month-notice';
            })
            ->once()
            ->andReturn($oneMonthWarningAccounts);

        $this->authUserRepository->shouldReceive('getAccountsUnactivatedOlderThan')
            ->withArgs(function ($olderThan) {
                return $olderThan >= new DateTime('-24 hours -1 second');
            })
            ->once()
            ->andReturn($unactivatedAccounts);
    }
}
