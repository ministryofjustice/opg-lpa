<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\AccountCleanup;

use Application\Model\Service\SharedSpace\SharedSpaceService;
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
    private array $config = [
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

    private string|false $originalOneLoginFlag;
    private MockInterface|UserRepositoryInterface $authUserRepository;
    private MockInterface|ApplicationRepositoryInterface $applicationRepository;
    private MockInterface|NotifyClient $notifyClient;
    private MockInterface|UsersService $usersService;
    private MockInterface|SharedSpaceService $sharedSpaceService;

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
        $this->sharedSpaceService = Mockery::mock(SharedSpaceService::class);
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

    public static function layWarningEmailProvider(): array
    {
        $weekFlag = AccountCleanupService::WARNING_1_WEEK_NOTICE;
        $monthFlag = AccountCleanupService::WARNING_1_MONTH_NOTICE;
        $unclaimedUserData = ['identity' => 'identity@example.com'];
        $layClaimedUserData = [
            'identity' => 'onelogin:urn:fdc:gov.uk:2022:sub-123',
            'one_login_sub' => 'urn:fdc:gov.uk:2022:sub-123',
            'one_login_email' => 'ol@example.com',
        ];

        return [
            'week, feature off, legacy account' => [
                false, $weekFlag, $unclaimedUserData, 'identity@example.com', '3e0cc4c8-0c2a-4d2a-808a-32407b2e6276',
            ],
            'week, feature on, lay unclaimed account' => [
                true, $weekFlag, $unclaimedUserData, 'identity@example.com', '1704ba7b-ded7-4664-abf0-6ef6f4343ad6',
            ],
            'week, feature on, lay claimed account' => [
                true, $weekFlag, $layClaimedUserData, 'ol@example.com', '399f2d80-9d3a-44c3-aa2e-8895d98b58fa',
            ],
            'month, feature off, legacy account' => [
                false, $monthFlag, $unclaimedUserData, 'identity@example.com', '0ef97354-9db2-4d52-a1cf-0aa762444cb1',
            ],
            'month, feature on, lay unclaimed account' => [
                true, $monthFlag, $unclaimedUserData, 'identity@example.com', 'e6a5d415-8198-4c07-9dcb-81a039b812e7',
            ],
            'month, feature on, lay claimed account' => [
                true, $monthFlag, $layClaimedUserData, 'ol@example.com', '1ccd99de-2067-457a-87de-cf5dabd13fff',
            ],
        ];
    }

    #[DataProvider('layWarningEmailProvider')]
    public function testCleanupSendsWarningEmailToLays(
        bool $oneLoginEnabled,
        string $warningType,
        array $userData,
        string $expectedEmail,
        string $expectedTemplateId,
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

    public static function sharedSpaceWarningEmailProvider(): array
    {
        $weekFlag = AccountCleanupService::WARNING_1_WEEK_NOTICE;
        $monthFlag = AccountCleanupService::WARNING_1_MONTH_NOTICE;
        $sharedSpaceMemberUserData = [
            'identity' => 'onelogin:urn:fdc:gov.uk:2022:sub-123',
            'one_login_sub' => 'urn:fdc:gov.uk:2022:sub-123',
            'one_login_email' => 'ol@example.com',
            'isActiveInSharedSpace' => true,
            'sharedSpaceName' => 'Example Shared Space',
            'sharedSpaceId' => 'shared-space-1',
        ];
        $sharedSpaceAdminUserData = $sharedSpaceMemberUserData + ['isSharedSpaceAdmin' => true];
        $weekDeletionDate = (new DateTime('-9 months +1 week'))->modify('+9 months')->format('j F Y');
        $monthDeletionDate = (new DateTime('-8 months'))->modify('+9 months')->format('j F Y');

        return [
            'week, shared space member' => [
                $weekFlag, $sharedSpaceMemberUserData, 'ol@example.com', '84c0ea7d-1c18-4032-bf17-ee482a536d6e',
                [
                    'deletionDate' =>  $weekDeletionDate,
                    'sharedSpaceName' => 'Example Shared Space',
                    'isOnlyAdmin' => false
                ]
            ],
            'month, shared space member' => [
                $monthFlag, $sharedSpaceMemberUserData, 'ol@example.com', '59b4ea4b-c10b-4788-b550-1ff20a43c454',
                [
                    'deletionDate' =>  $monthDeletionDate,
                    'sharedSpaceName' => 'Example Shared Space',
                    'isOnlyAdmin' => false
                ]
            ],
            'week, shared space multiple admins' => [
                $weekFlag, $sharedSpaceAdminUserData, 'ol@example.com', '84c0ea7d-1c18-4032-bf17-ee482a536d6e',
                [
                    'deletionDate' =>  $weekDeletionDate,
                    'sharedSpaceName' => 'Example Shared Space',
                    'isOnlyAdmin' => false
                ]
            ],
            'week, shared space only admin' => [
                $weekFlag, $sharedSpaceAdminUserData, 'ol@example.com', '84c0ea7d-1c18-4032-bf17-ee482a536d6e',
                [
                    'deletionDate' =>  $weekDeletionDate,
                    'sharedSpaceName' => 'Example Shared Space',
                    'isOnlyAdmin' => true
                ]
            ],
            'month, shared space multiple admins' => [
                $monthFlag, $sharedSpaceAdminUserData, 'ol@example.com', '59b4ea4b-c10b-4788-b550-1ff20a43c454',
                [
                    'deletionDate' =>  $monthDeletionDate,
                    'sharedSpaceName' => 'Example Shared Space',
                    'isOnlyAdmin' => false
                ]
            ],
            'month, shared space only admin' => [
                $monthFlag, $sharedSpaceAdminUserData, 'ol@example.com', '59b4ea4b-c10b-4788-b550-1ff20a43c454',
                [
                    'deletionDate' =>  $monthDeletionDate,
                    'sharedSpaceName' => 'Example Shared Space',
                    'isOnlyAdmin' => true
                ]
            ],
        ];
    }

    #[DataProvider('sharedSpaceWarningEmailProvider')]
    public function testCleanupSendsWarningEmailToSharedSpaceMembers(
        string $warningType,
        array $userData,
        string $expectedEmail,
        string $expectedTemplateId,
        array $expectedPersonalisations,
    ): void {

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

        if (!empty($userData['isSharedSpaceAdmin'])) {
            $this->sharedSpaceService->shouldReceive('hasExactlyOneActiveAdmin')
                ->with('shared-space-1')
                ->once()
                ->andReturn($expectedPersonalisations['isOnlyAdmin']);
        }

        $this->notifyClient->shouldReceive('sendEmail')
            ->withArgs([$expectedEmail, $expectedTemplateId, $expectedPersonalisations])
            ->once();

        $this->authUserRepository->shouldReceive('setInactivityFlag')
            ->withArgs([1, $warningType])
            ->once();

        $this->logger->shouldNotReceive('alert');

        $this->assertSame(0, $this->buildService()->cleanup());
    }

    public function testCleanupDryRunDoesNotDeleteFlagOrSend(): void
    {
        $sharedSpaceMember = new User([
            'id' => 2,
            'identity' => 'onelogin:urn:fdc:gov.uk:2022:sub-123',
            'one_login_sub' => 'urn:fdc:gov.uk:2022:sub-123',
            'one_login_email' => 'ol@example.com',
            'isActiveInSharedSpace' => true,
            'sharedSpaceName' => 'Example Shared Space',
            'sharedSpaceId' => 'shared-space-1',
            'last_login' => new DateTime('-9 months +1 week'),
        ]);
        $monthUser = new User(['id' => 3, 'identity' => 'unit@test.com', 'last_login' => new DateTime('-8 months')]);

        $this->setAccountsExpectations(
            [new User(['id' => 1])],
            [$sharedSpaceMember],
            [$monthUser],
            [new User(['id' => 4])]
        );

        $this->notifyClient->shouldNotReceive('sendEmail');
        $this->usersService->shouldNotReceive('delete');
        $this->authUserRepository->shouldNotReceive('setInactivityFlag');
        $this->sharedSpaceService->shouldNotReceive('hasExactlyOneActiveAdmin');
        $this->logger->shouldNotReceive('alert');
        $this->logger->shouldNotReceive('warning');

        $this->logger->shouldReceive('info')
            ->with('Dry run: would delete expired account', ['user-id' => 1])
            ->once();
        $this->logger->shouldReceive('info')
            ->with('Dry run: would delete unactivated account', ['user-id' => 4])
            ->once();
        $this->logger->shouldReceive('info')
            ->with('Dry run: would send account expiry notification', Mockery::on(function (array $extra) {
                return $extra['user-id'] == 2
                    && $extra['warning-type'] === AccountCleanupService::WARNING_1_WEEK_NOTICE
                    && $extra['template-id'] === '84c0ea7d-1c18-4032-bf17-ee482a536d6e'
                    && $extra['personalisations']['sharedSpaceName'] === 'Example Shared Space';
            }))
            ->once();
        $this->logger->shouldReceive('info')
            ->with('Dry run: would send account expiry notification', Mockery::on(function (array $extra) {
                return $extra['user-id'] == 3
                    && $extra['warning-type'] === AccountCleanupService::WARNING_1_MONTH_NOTICE;
            }))
            ->once();
        $this->logger->shouldReceive('info')
            ->with('Account cleanup dry run completed', Mockery::on(function (array $extra) {
                return $extra['dryRun'] === true
                    && $extra['expiredAccountsDeletedCount'] === 1
                    && $extra['expiryAccountsWarning1WeekCount'] === 1
                    && $extra['expiryAccountsWarning1MonthCount'] === 1
                    && $extra['unactivatedAccountsDeletedCount'] === 1;
            }))
            ->once();

        $this->assertSame(0, $this->buildService()->cleanup(true));
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
        $service->setSharedSpaceService($this->sharedSpaceService);

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
