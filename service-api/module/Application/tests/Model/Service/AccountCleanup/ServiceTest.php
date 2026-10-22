<?php

namespace ApplicationTest\Model\Service\AccountCleanup;

use ArrayIterator;
use Application\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Postgres\UserModel as User;
use Application\Model\Service\Users\Service as UsersService;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use DateInterval;
use DateTime;
use Exception;
use Mockery;
use Mockery\MockInterface;

class ServiceTest extends AbstractServiceTestCase
{
    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository;

    /**
     * @var MockInterface|ApplicationRepositoryInterface
     */
    private $applicationRepository;

    /**
     * @var array
     */
    private $config = [
        'stack' => [
            'name' => 'unit_test'
        ],
        'notify' => [
            'api' => [
                'key' => 'DUMMYKEY',
            ],
        ],
        'log' => [
            'sns' => [
                'endpoints' => [
                    'info' => 'info_endpoint',
                ],
                'client' => []
            ]
        ],
        'admin' => [
            'account_cleanup_notification_recipients' => [
                'test1@example.com',
                'test2@example.com',
                'test3@example.com',
            ],
        ],
    ];

    /**
     * @var MockInterface|NotifyClient
     */
    private $notifyClient;

    /**
     * @var MockInterface|UsersService
     */
    private $usersService;

    private string|false $originalOneLoginFlag;

    public function setUp(): void
    {
        parent::setUp();

        $this->originalOneLoginFlag = getenv(Feature::OneLogin->value);
        putenv(Feature::OneLogin->value);

        //  Set up the services so they can be enhanced for each test
        $this->applicationRepository = Mockery::mock(ApplicationRepositoryInterface::class);

        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->notifyClient = Mockery::mock(NotifyClient::class);

        $this->usersService = Mockery::mock(UsersService::class);
    }

    protected function tearDown(): void
    {
        putenv(Feature::OneLogin->value . ($this->originalOneLoginFlag === false ? '' : '=' . $this->originalOneLoginFlag));

        parent::tearDown();
    }

    public function testCleanupNone()
    {
        $this->setAccountsExpectations();

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            )
            // Should be called once per admin email address.
            ->times(count($this->config['admin']['account_cleanup_notification_recipients']));

        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupNoneSnsPublishException()
    {
        $this->setAccountsExpectations();

        $this->notifyClient->shouldReceive('sendEmail')
            ->andThrow(new NotifyException('Test exception'));

        $this->logger->shouldReceive('alert')->withArgs(function ($message, $extra) {
            return $message === 'Unable to send admin notification message' && array_key_exists('exception', $extra);
        });

        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(1, $result);
    }

    public function testCleanupExpiredAccounts()
    {
        $this->setAccountsExpectations([new User(['id' => 1])]);

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            );

        $this->usersService->shouldReceive('delete')
            ->withArgs([1, 'expired']);

        $this->applicationRepository->shouldReceive('fetchByUserId')
            ->with(1)
            ->andReturn(new ArrayIterator([]));

        $this->applicationRepository->shouldReceive('deleteById')
            ->withArgs([1])
            ->andReturnNull();

        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupExpiredAccountsException()
    {
        $this->setAccountsExpectations([new User(['id' => 1])]);

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            );

        $this->usersService->shouldReceive('delete')
            ->withArgs([1, 'expired']);

        $this->applicationRepository->shouldReceive('fetchByUserId')
            ->with(1)
            ->andReturn(new ArrayIterator([]));

        $this->applicationRepository->shouldReceive('deleteById')
            ->withArgs([1])
            ->andReturnNull();

        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public static function warningEmailProvider(): array
    {
        $weekFlag = AccountCleanupService::WARNING_1_WEEK_NOTICE;
        $monthFlag = AccountCleanupService::WARNING_1_MONTH_NOTICE;

        return [
            'week, feature off, legacy account' => [
                false, $weekFlag, ['identity' => 'identity@example.com'],
                'identity@example.com', '3e0cc4c8-0c2a-4d2a-808a-32407b2e6276',
            ],
            'week, feature on, unclaimed account' => [
                true, $weekFlag, ['identity' => 'identity@example.com'],
                'identity@example.com', '1704ba7b-ded7-4664-abf0-6ef6f4343ad6',
            ],
            'week, feature on, claimed account' => [
                true, $weekFlag,
                [
                    'identity' => 'onelogin:urn:fdc:gov.uk:2022:sub-123',
                    'one_login_sub' => 'urn:fdc:gov.uk:2022:sub-123',
                    'one_login_email' => 'ol@example.com',
                ],
                'ol@example.com', '399f2d80-9d3a-44c3-aa2e-8895d98b58fa',
            ],
            'month, feature off, legacy account' => [
                false, $monthFlag, ['identity' => 'identity@example.com'],
                'identity@example.com', '0ef97354-9db2-4d52-a1cf-0aa762444cb1',
            ],
            'month, feature on, unclaimed account' => [
                true, $monthFlag, ['identity' => 'identity@example.com'],
                'identity@example.com', 'e6a5d415-8198-4c07-9dcb-81a039b812e7',
            ],
            'month, feature on, claimed account' => [
                true, $monthFlag,
                [
                    'identity' => 'onelogin:urn:fdc:gov.uk:2022:sub-123',
                    'one_login_sub' => 'urn:fdc:gov.uk:2022:sub-123',
                    'one_login_email' => 'ol@example.com',
                ],
                'ol@example.com', '1ccd99de-2067-457a-87de-cf5dabd13fff',
            ],
        ];
    }

    #[DataProvider('warningEmailProvider')]
    public function testCleanupSendsWarningEmail(
        bool $oneLoginEnabled,
        string $warningType,
        array $userData,
        string $expectedEmail,
        string $expectedTemplateId
    ): void {
        putenv(Feature::OneLogin->value . '=' . ($oneLoginEnabled ? 'true' : 'false'));

        $isWeek = $warningType === AccountCleanupService::WARNING_1_WEEK_NOTICE;
        $lastLoginDate = new DateTime($isWeek ? '-9 months +1 week' : '-8 months');
        $user = new User($userData + ['id' => 1, 'last_login' => clone $lastLoginDate]);

        $this->setAccountsExpectations([], $isWeek ? [$user] : [], $isWeek ? [] : [$user]);

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            );

        $lastLoginDate->add(DateInterval::createFromDateString('+9 months'));

        $this->notifyClient->shouldReceive('sendEmail')
            ->withArgs([$expectedEmail, $expectedTemplateId, [
                'deletionDate' => $lastLoginDate->format('j F Y'),
            ]])
            ->once();

        $this->authUserRepository->shouldReceive('setInactivityFlag')
            ->withArgs([1, $warningType])
            ->once();

        $this->logger->shouldNotReceive('alert');

        $this->assertSame(0, $this->buildService()->cleanup());
    }

    public function testCleanupOneWeekWarningAccountsNotifyException()
    {
        $this->setAccountsExpectations([], [new User([
            'id' => 1,
            'identity' => 'unit@test.com',
            'last_login' => new DateTime('-9 months +1 week')
        ])]);

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            );

        $this->notifyClient->shouldReceive('sendEmail')
            ->once()
            ->andThrow(new NotifyException('Unit test exception'));

        $this->logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $extra) {
                $this->assertSame('Unable to send account expiry notification', $message);
                $this->assertInstanceOf(NotifyException::class, $extra['exception']);
                $this->assertSame('Unit test exception', $extra['exception']->getMessage());

                return true;
            });

        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupOneWeekWarningAccountsException()
    {
        $this->setAccountsExpectations([], [new User([
            'id' => 1,
            'identity' => 'unit@test.com',
            'last_login' => new DateTime('-9 months +1 week')
        ])]);

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            );

        $this->notifyClient->shouldReceive('sendEmail')
            ->once()
            ->andThrow(new Exception('Unit test exception'));

        $this->logger->shouldReceive('alert')
            ->once()
            ->withArgs(function (string $message, array $extra) {
                $this->assertSame('Unable to send account expiry notification', $message);
                $this->assertSame('Unit test exception', $extra['exception']->getMessage());

                return true;
            });


        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    public function testCleanupUnactivatedAccounts()
    {
        $this->setAccountsExpectations([], [], [], [new User(['id' => 1])]);

        $this->notifyClient->shouldReceive('sendEmail')
            ->with(
                Mockery::type('string'),
                AccountCleanupService::CLEANUP_NOTIFICATION_TEMPLATE,
                Mockery::type('array')
            );

        $this->usersService->shouldReceive('delete')
            ->withArgs([1, 'unactivated']);

        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        $result = $service->cleanup();

        // Function doesn't return anything
        $this->assertEquals(null, $result);
    }

    private function buildService(?array $config = null): AccountCleanupService
    {
        $service = new AccountCleanupService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setUserRepository($this->authUserRepository);
        $service->setConfig($config ?? $this->config);
        $service->setNotifyClient($this->notifyClient);
        $service->setUsersService($this->usersService);
        $service->setLogger($this->logger);

        return $service;
    }

    private function setAccountsExpectations(
        array $expiredAccounts = [],
        array $oneWeekWarningAccounts = [],
        array $oneMonthWarningAccounts = [],
        array $unactivatedAccounts = []
    ) {
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
