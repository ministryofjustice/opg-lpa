<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\SharedSpace;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Entity\MemberInvite;
use Application\Model\Service\SharedSpace\InviteAlreadyExistsException;
use Application\Model\Service\SharedSpace\InviteExpiredException;
use Application\Model\Service\SharedSpace\InviteNotFoundException;
use Application\Model\Service\SharedSpace\InviteNotMatchedException;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use Application\Model\Service\SharedSpace\NoOtherAdminException;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use DateTime;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\Logging\MetricsLogger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class SharedSpaceServiceTest extends MockeryTestCase
{
    private MockInterface|SharedSpaceRepositoryInterface $sharedSpaceRepository;
    private MockInterface|ApplicationRepositoryInterface $applicationRepository;
    private MockInterface|UserRepositoryInterface $userRepository;
    private MockInterface|LogRepositoryInterface $logRepository;
    private MockInterface|LoggerInterface $logger;
    private MockInterface|MetricsLogger $metrics;
    private SharedSpaceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedSpaceRepository = Mockery::mock(SharedSpaceRepositoryInterface::class);
        $this->applicationRepository = Mockery::mock(ApplicationRepositoryInterface::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->logRepository = Mockery::mock(LogRepositoryInterface::class);

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info')->byDefault();
        $this->logger->shouldReceive('log')->byDefault();
        $this->logger->shouldReceive('error')->byDefault();

        $this->metrics = Mockery::mock(MetricsLogger::class);

        $this->service = new SharedSpaceService(
            $this->sharedSpaceRepository,
            $this->applicationRepository,
            $this->userRepository,
            $this->logRepository,
            $this->logger,
            $this->metrics,
        );
    }

    public function testUserAlreadyInSharedSpace()
    {
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->with($userId)
            ->once()
            ->andReturn('existing-shared-space');

        $this->sharedSpaceRepository->shouldNotReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldNotReceive('create');

        $this->expectException(UserAlreadyInSharedSpaceException::class);

        $this->service->create('My Space', $userId);
    }

    public function testCreateSuccessCommitsTransaction()
    {
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->with($userId)
            ->once()
            ->andReturn(null);

        $this->sharedSpaceRepository->shouldReceive('beginTransaction')->once()->ordered();
        $this->sharedSpaceRepository->shouldReceive('create')->once()->ordered()->andReturn(true);

        $this->applicationRepository->shouldReceive('setSharedSpaceOwner')
            ->once()
            ->ordered()
            ->andReturn(3);

        $this->sharedSpaceRepository->shouldReceive('addMember')->once()->ordered()->andReturn(true);
        $this->sharedSpaceRepository->shouldReceive('commit')->once()->ordered();
        $this->sharedSpaceRepository->shouldNotReceive('rollback');

        $this->metrics->shouldReceive('sharedSpaceCreated')->once()->with(Mockery::type('string'), 3);

        $result = $this->service->create('My Space', $userId);

        $this->assertIsArray($result);
        $this->assertSame(3, $result['lpasMoved']);
        $this->assertSame('My Space', $result['name']);
        $this->assertNotEmpty($result['sharedSpaceId']);
    }

    public function testCreateFailureRollsBackTransaction()
    {
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->with($userId)
            ->once()
            ->andReturn(null);

        $this->sharedSpaceRepository->shouldReceive('beginTransaction')->once()->ordered();
        $this->sharedSpaceRepository->shouldReceive('create')->once()->ordered()->andReturn(false);

        $this->applicationRepository->shouldNotReceive('setSharedSpaceOwner');
        $this->sharedSpaceRepository->shouldNotReceive('addMember');
        $this->sharedSpaceRepository->shouldNotReceive('commit');
        $this->sharedSpaceRepository->shouldReceive('rollback')->once()->ordered();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create shared space');

        $this->service->create('My Space', $userId);
    }

    public function testGetName()
    {
        $this->sharedSpaceRepository->shouldReceive('getSharedSpace')
            ->with('an-id')
            ->andReturn('a name');

        $result = $this->service->getName('an-id');
        $this->assertEquals('a name', $result);
    }

    public function testCountMembers()
    {
        $this->sharedSpaceRepository->shouldReceive('countMembers')
            ->with('my-space')
            ->once()
            ->andReturn(3);

        $result = $this->service->countMembers('my-space');
        $this->assertSame(3, $result);
    }

    public function testGetMembers()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getMembers')
            ->with($sharedSpaceId)
            ->andReturn([
                new SharedSpaceMember(
                    [
                        'sharedSpaceName' => 'My Space',
                        'userId' => 'user1',
                        'isAdmin' => true,
                        'isActive' => true,
                        'name' => ['first' => 'me'],
                        'email' => '1@example.com',
                        'lastLoginAt' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.uO'),
                    ]
                ),
                new SharedSpaceMember(
                    [
                        'sharedSpaceName' => 'My Space',
                        'userId' => 'user2',
                        'isAdmin' => true,
                        'isActive' => false,
                        'name' => ['first' => 'you'],
                        'email' => '2@example.com',
                        'lastLoginAt' => (new DateTime('2020-01-02'))->format('Y-m-d\TH:i:s.uO'),
                    ]
                ),
                new SharedSpaceMember(
                    [
                        'sharedSpaceName' => 'My Space',
                        'userId' => 'user3',
                        'isAdmin' => false,
                        'isActive' => true,
                        'name' => ['first' => 'them'],
                        'email' => '3@example.com',
                        'lastLoginAt' => (new DateTime('2020-01-03'))->format('Y-m-d\TH:i:s.uO'),
                    ]
                ),
            ]);

        $result = $this->service->getMembers($sharedSpaceId);

        $this->assertEquals([
            [
                'userId' => 'user1',
                'name' => new Name(['first' => 'me']),
                'email' => '1@example.com',
                'lastLoginAt' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.uO'),
                'isActive' => true,
                'isAdmin' => true,
                'sharedSpaceName' => 'My Space',
            ],
            [
                'userId' => 'user2',
                'name' => new Name(['first' => 'you']),
                'email' => '2@example.com',
                'lastLoginAt' => (new DateTime('2020-01-02'))->format('Y-m-d\TH:i:s.uO'),
                'isActive' => false,
                'isAdmin' => true,
                'sharedSpaceName' => 'My Space'
            ],
            [
                'userId' => 'user3',
                'name' => new Name(['first' => 'them']),
                'email' => '3@example.com',
                'lastLoginAt' => (new DateTime('2020-01-03'))->format('Y-m-d\TH:i:s.uO'),
                'isActive' => true,
                'isAdmin' => false,
                'sharedSpaceName' => 'My Space'
            ],
        ], $result);
    }

    public function testGetMembersPaginated()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getMembersPaginated')
            ->with($sharedSpaceId, 20, 10)
            ->andReturn([
                'results' => [
                    new SharedSpaceMember(
                        [
                            'sharedSpaceName' => 'My Space',
                            'userId' => 'user1',
                            'isAdmin' => true,
                            'isActive' => true,
                            'name' => ['first' => 'me'],
                            'email' => '1@example.com',
                            'lastLoginAt' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.uO'),
                        ]
                    ),
                ],
                'total' => 25,
            ]);

        $result = $this->service->getMembersPaginated($sharedSpaceId, 3, 10);

        $this->assertEquals([
            'results' => [
                [
                    'userId' => 'user1',
                    'name' => new Name(['first' => 'me']),
                    'email' => '1@example.com',
                    'lastLoginAt' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.uO'),
                    'isActive' => true,
                    'isAdmin' => true,
                    'sharedSpaceName' => 'My Space',
                ],
            ],
            'total' => 25,
        ], $result);
    }

    public function testGetMember()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getMember')
            ->with($sharedSpaceId, 'user2')
            ->andReturn(
                new SharedSpaceMember(
                    [
                        'sharedSpaceName' => 'My Space',
                        'userId' => 'user2',
                        'isAdmin' => false,
                        'isActive' => true,
                        'name' => ['first' => 'you'],
                        'email' => '2@example.com',
                        'lastLoginAt' => (new DateTime('2020-01-02'))->format('Y-m-d\TH:i:s.uO'),
                    ]
                )
            );

        $result = $this->service->getMember($sharedSpaceId, 'user2');

        $this->assertEquals([
            'userId' => 'user2',
            'sharedSpaceName' => 'My Space',
            'name' => new Name(['first' => 'you']),
            'email' => new EmailAddress(['address' => '2@example.com']),
            'lastLoginAt' => (new DateTime('2020-01-02'))->format('Y-m-d\TH:i:s.uO'),
            'isActive' => true,
            'isAdmin' => false,
        ], $result);
    }

    public function testGetMemberReturnsNullWhenMemberNotInSharedSpace()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getMember')
            ->with($sharedSpaceId, 'unknown-user')
            ->andReturn(null);

        $result = $this->service->getMember($sharedSpaceId, 'unknown-user');

        $this->assertNull($result);
    }

    private function existingMember(bool $isAdmin, bool $isActive): SharedSpaceMember
    {
        return new SharedSpaceMember(['userId' => 'user1', 'isAdmin' => $isAdmin, 'isActive' => $isActive]);
    }

    /**
     * @return array<string, array{bool, bool, bool, bool, string[]}>
     */
    public static function memberUpdateProvider(): array
    {
        return [
            'promoted'            => [false, true, true, true, ['sharedSpaceMemberPromotedToAdmin']],
            'demoted'             => [true, true, false, true, ['sharedSpaceMemberDemotedFromAdmin']],
            'suspended'           => [false, true, false, false, ['sharedSpaceMemberSuspended']],
            'activated'           => [false, false, false, true, ['sharedSpaceMemberActivated']],
            'promoted and suspended' => [
                false,
                true,
                true,
                false,
                ['sharedSpaceMemberPromotedToAdmin', 'sharedSpaceMemberSuspended'],
            ],
            'unchanged'           => [true, true, true, true, []],
        ];
    }

    /**
     * @param string[] $expectedMethods
     */
    #[DataProvider('memberUpdateProvider')]
    public function testUpdateMemberEmitsAMetricForEachChange(
        bool $wasAdmin,
        bool $wasActive,
        bool $isAdmin,
        bool $isActive,
        array $expectedMethods,
    ) {
        $this->sharedSpaceRepository->shouldReceive('getMember')
            ->with('my-space', 'user1')
            ->once()
            ->andReturn($this->existingMember($wasAdmin, $wasActive));
        $this->sharedSpaceRepository->shouldReceive('updateMember')
            ->with('my-space', 'user1', $isAdmin, $isActive)
            ->once();

        $allMethods = [
            'sharedSpaceMemberPromotedToAdmin',
            'sharedSpaceMemberDemotedFromAdmin',
            'sharedSpaceMemberSuspended',
            'sharedSpaceMemberActivated',
        ];
        foreach ($allMethods as $method) {
            if (in_array($method, $expectedMethods, true)) {
                $this->metrics->shouldReceive($method)->once()->with('my-space', 'user1');
            } else {
                $this->metrics->shouldNotReceive($method);
            }
        }

        $this->service->updateMember('my-space', 'user1', $isAdmin, $isActive);
    }

    public function testUpdateMemberIsAdminThrowsWhenMemberNotFound()
    {
        $this->sharedSpaceRepository->shouldReceive('getMember')->once()->andReturn(null);
        $this->sharedSpaceRepository->shouldNotReceive('updateMember');

        $this->expectException(MemberNotInSharedSpaceException::class);

        $this->service->updateMember('my-space', 'user1', true, false);
    }

    public function testUpdateMemberThrowsWhenUpdateAffectsNoRow()
    {
        $this->sharedSpaceRepository->shouldReceive('getMember')
            ->once()
            ->andReturn($this->existingMember(false, true));
        $this->sharedSpaceRepository->shouldReceive('updateMember')
            ->once()
            ->andThrow(new MemberNotInSharedSpaceException());

        $this->expectException(MemberNotInSharedSpaceException::class);

        $this->service->updateMember('my-space', 'user1', true, false);
    }

    public function testUpdateMemberIsAdminRethrowsException()
    {
        $this->sharedSpaceRepository->shouldReceive('getMember')
            ->once()
            ->andReturn($this->existingMember(false, true));
        $this->sharedSpaceRepository->shouldReceive('updateMember')
            ->once()
            ->andThrow(new RuntimeException('boom'));

        $this->expectException(RuntimeException::class);

        $this->service->updateMember('my-space', 'user1', true, false);
    }

    public function testIsAdminReturnsTrueForAdminMember()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->service->isAdmin($sharedSpaceId, $userId));
    }

    public function testIsAdminReturnsFalseForNonAdminMember()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('isAdmin')
            ->with($sharedSpaceId, $userId)
            ->once()
            ->andReturn(false);

        $this->assertFalse($this->service->isAdmin($sharedSpaceId, $userId));
    }

    public function testDeleteMember()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'my-user';
        $userToDeleteId = 'delete-me';

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->userRepository->shouldReceive('getById')
            ->with($userToDeleteId)
            ->andReturn($user);
        $this->userRepository->shouldReceive('delete')
            ->with($userToDeleteId)
            ->andReturn(true);

        $this->logRepository->shouldReceive('addLog')
            ->with(Mockery::on(function ($args): bool {
                return $args['type'] === 'account-deleted'
                    && $args['reason'] === 'Deleted from shared space';
            }));

        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('deleteMember')
            ->with($sharedSpaceId, $userToDeleteId);
        $this->sharedSpaceRepository->shouldReceive('commit');

        $this->metrics->shouldReceive('sharedSpaceMemberDeletedByAdmin')
            ->once()
            ->with($sharedSpaceId, $userId, $userToDeleteId);
        $this->metrics->shouldReceive('userDeleted')
            ->once()
            ->with('removed_from_shared_space', $userToDeleteId);

        $this->service->deleteMember($sharedSpaceId, $userId, $userToDeleteId);
    }

    public function testDeleteMemberWhenUserNotDeleted()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'my-user';
        $userToDeleteId = 'delete-me';

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->userRepository->shouldReceive('getById')
            ->andReturn($user);
        $this->userRepository->shouldReceive('delete')
            ->andReturn(false);

        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('deleteMember');
        $this->sharedSpaceRepository->shouldReceive('rollback');

        $this->metrics->shouldNotReceive('userDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedBySelf');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedByAdmin');

        $this->expectException(\RuntimeException::class);
        $this->service->deleteMember($sharedSpaceId, $userId, $userToDeleteId);
    }

    public function testGetInvites()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getInvites')
            ->with($sharedSpaceId)
            ->andReturn([
                new MemberInvite(
                    id: 1,
                    userId: '',
                    sharedSpaceId: '',
                    firstNames: 'a',
                    lastName: 'b',
                    email: 'c',
                    isAdmin: false,
                    code: '',
                    created: new DateTime(),
                    expires: new DateTime('+1 minute'),
                ),
                new MemberInvite(
                    id: 2,
                    userId: '',
                    sharedSpaceId: '',
                    firstNames: 'd',
                    lastName: 'e',
                    email: 'f',
                    isAdmin: false,
                    code: '',
                    created: new DateTime(),
                    expires: new DateTime('-1 minute'),
                ),
            ]);

        $result = $this->service->getInvites($sharedSpaceId);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('a b', $result[0]['fullName']);
        $this->assertEquals('c', $result[0]['email']);
        $this->assertFalse($result[0]['isExpired']);
        $this->assertArrayHasKey('createdAt', $result[0]);

        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals('d e', $result[1]['fullName']);
        $this->assertEquals('f', $result[1]['email']);
        $this->assertTrue($result[1]['isExpired']);
        $this->assertArrayHasKey('createdAt', $result[1]);
    }

    public function testGetInvitesPaginated()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getInvitesPaginated')
            ->with($sharedSpaceId, 10, 5)
            ->andReturn([
                'results' => [
                    new MemberInvite(
                        id: 1,
                        userId: '',
                        sharedSpaceId: '',
                        firstNames: 'a',
                        lastName: 'b',
                        email: 'c',
                        isAdmin: false,
                        code: '',
                        created: new DateTime(),
                        expires: new DateTime('+1 minute'),
                    ),
                ],
                'total' => 12,
            ]);

        $result = $this->service->getInvitesPaginated($sharedSpaceId, 3, 5);

        $this->assertCount(1, $result['results']);
        $this->assertEquals(1, $result['results'][0]['id']);
        $this->assertEquals('a b', $result['results'][0]['fullName']);
        $this->assertEquals('c', $result['results'][0]['email']);
        $this->assertFalse($result['results'][0]['isExpired']);
        $this->assertEquals(12, $result['total']);
    }

    public function testInvite()
    {
        $memberInvite = new MemberInvite(
            id: 1,
            userId: 'my user',
            sharedSpaceId: 'my space',
            firstNames: 'a',
            lastName: 'b',
            email: 'c',
            isAdmin: false,
            code: '12341234',
            created: new DateTime(),
            expires: new DateTime('2024-01-02T12:13:14'),
        );

        $this->sharedSpaceRepository->shouldReceive('hasInvite')
            ->with($memberInvite->sharedSpaceId, $memberInvite->email)
            ->andReturn(false);

        $this->sharedSpaceRepository->shouldReceive('hasMemberWithEmail')
            ->with($memberInvite->sharedSpaceId, $memberInvite->email)
            ->andReturn(false);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpace')
            ->with($memberInvite->sharedSpaceId)
            ->andReturn('my space');

        $this->sharedSpaceRepository->shouldReceive('createInvite')
            ->with($memberInvite)
            ->andReturn(1234);

        $this->metrics->shouldReceive('sharedSpaceMemberInvited')->once()->with(false, 'my space', 1234, 'my user');

        $result = $this->service->invite($memberInvite);

        $this->assertEquals([
            'id' => 1234,
            'sharedSpaceName' => 'my space',
            'inviteCode' => $memberInvite->code,
            'expires' => '2024-01-02T12:13:14.0000000',
        ], $result);
    }

    public function testInviteWhenInviteExists()
    {
        $memberInvite = new MemberInvite(
            id: 1,
            userId: 'my user',
            sharedSpaceId: 'my space',
            firstNames: 'a',
            lastName: 'b',
            email: 'c',
            isAdmin: false,
            code: '12341234',
            created: new DateTime(),
            expires: new DateTime('-1 minute'),
        );

        $this->sharedSpaceRepository->shouldReceive('hasInvite')
            ->with($memberInvite->sharedSpaceId, $memberInvite->email)
            ->andReturn(true);

        $this->expectException(InviteAlreadyExistsException::class);

        $this->service->invite($memberInvite);
    }

    public function testInviteWhenEmailAlreadyInSharedSpace()
    {
        $memberInvite = new MemberInvite(
            id: 1,
            userId: 'my user',
            sharedSpaceId: 'my space',
            firstNames: 'a',
            lastName: 'b',
            email: 'c',
            isAdmin: false,
            code: '12341234',
            created: new DateTime(),
            expires: new DateTime('-1 minute'),
        );

        $this->sharedSpaceRepository->shouldReceive('hasInvite')
            ->with($memberInvite->sharedSpaceId, $memberInvite->email)
            ->andReturn(false);

        $this->sharedSpaceRepository->shouldReceive('hasMemberWithEmail')
            ->with($memberInvite->sharedSpaceId, $memberInvite->email)
            ->andReturn(true);

        $this->expectException(UserAlreadyInSharedSpaceException::class);

        $this->service->invite($memberInvite);
    }

    public function testRevokeInvite()
    {
        $sharedSpaceId = 'space-id';
        $inviteId = 5;
        $revokedByUserId = 'user-id';

        $this->sharedSpaceRepository->shouldReceive('deleteInvite')
            ->with(5);

        $this->metrics->shouldReceive('sharedSpaceMemberInviteRevoked')
            ->once()
            ->with($sharedSpaceId, $inviteId, $revokedByUserId);

        $this->service->revokeInvite($sharedSpaceId, $inviteId, $revokedByUserId);
    }

    public function testRevokeInviteWhenRepositoryExceptionIsThrown()
    {
        $sharedSpaceId = 'space-id';
        $inviteId = 5;
        $revokedByUserId = 'user-id';

        $this->sharedSpaceRepository->shouldReceive('deleteInvite')
            ->with(5)
            ->andThrow(new \RuntimeException('Database error'));

        $this->logger->shouldReceive('error')
            ->with(
                'Unable to revoke shared space invite: Database error',
                [
                    'shared_space_id' => $sharedSpaceId,
                    'invite_id'       => $inviteId,
                ]
            );

        $this->expectException(RuntimeException::class);

        $this->service->revokeInvite($sharedSpaceId, $inviteId, $revokedByUserId);
    }

    public function testResendInvite()
    {
        $sharedSpaceId = 'space-id';
        $expires = new DateTime('2026-11-20 10:00:00');

        $this->sharedSpaceRepository->shouldReceive('renewInvite')
            ->once()
            ->with(5, $sharedSpaceId, '87654321', $expires);

        $this->sharedSpaceRepository->shouldReceive('getInvites')
            ->with($sharedSpaceId)
            ->andReturn([
                new MemberInvite(
                    id: 4,
                    userId: 'admin',
                    sharedSpaceId: $sharedSpaceId,
                    firstNames: 'Other',
                    lastName: 'Person',
                    email: 'other@example.com',
                    isAdmin: false,
                    code: '11111111',
                    created: new DateTime(),
                    expires: new DateTime('+1 day'),
                ),
                new MemberInvite(
                    id: 5,
                    userId: 'admin',
                    sharedSpaceId: $sharedSpaceId,
                    firstNames: 'Alt',
                    lastName: 'Person',
                    email: 'alt@example.com',
                    isAdmin: false,
                    code: '87654321',
                    created: new DateTime(),
                    expires: $expires,
                ),
            ]);

        $this->sharedSpaceRepository->shouldReceive('getSharedSpace')
            ->with($sharedSpaceId)
            ->andReturn('My Space');

        $this->metrics->shouldReceive('sharedSpaceMemberInviteResent')
            ->once()
            ->with($sharedSpaceId, 5, 'admin-id');

        $result = $this->service->resendInvite($sharedSpaceId, 5, '87654321', $expires, 'admin-id');

        $this->assertEquals([
            'firstNames' => 'Alt',
            'fullName' => 'Alt Person',
            'email' => 'alt@example.com',
            'sharedSpaceName' => 'My Space',
            'inviteCode' => '87654321',
            'expires' => $expires->format('Y-m-d\TH:i:s.u0'),
        ], $result);
    }

    public function testResendInviteWhenInviteIsNotInSharedSpace()
    {
        $this->sharedSpaceRepository->shouldReceive('renewInvite')
            ->andThrow(new InviteNotFoundException());

        $this->logger->shouldReceive('error')
            ->once()
            ->with(
                'Unable to resend shared space invite',
                Mockery::on(fn (array $context) => $context['shared_space_id'] === 'space-id'
                    && $context['invite_id'] === 5
                    && $context['exception'] instanceof InviteNotFoundException),
            );

        $this->metrics->shouldNotReceive('sharedSpaceMemberInviteResent');

        $this->expectException(InviteNotFoundException::class);

        $this->service->resendInvite('space-id', 5, '87654321', new DateTime(), 'admin-id');
    }

    public function testJoin()
    {
        $userId = 'my user';
        $sharedSpaceName = 'My Space';
        $accessCode = '1234';
        $email = 'user@example.com';

        $invite = new MemberInvite(
            id: 1,
            firstNames: 'a',
            lastName: 'b',
            email: 'c',
            expires: new DateTime('+1 minute'),
            userId: 'me',
            sharedSpaceId: 'some-space',
            isAdmin: false,
            code: '',
            created: new DateTime(),
        );

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('oneLoginEmail')
            ->andReturn($invite->email);

        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->with($userId)
            ->andReturn(null);
        $this->sharedSpaceRepository->shouldReceive('getInvite')
            ->with($accessCode, $sharedSpaceName, $email)
            ->andReturn($invite);
        $this->userRepository->shouldReceive('getById')
            ->with($userId)
            ->andReturn($user);
        $this->sharedSpaceRepository->shouldReceive('addMember')
            ->with($invite->sharedSpaceId, $userId, $invite->isAdmin);
        $this->sharedSpaceRepository->shouldReceive('deleteInvites')
            ->with($email);
        $this->sharedSpaceRepository->shouldReceive('commit');

        $this->applicationRepository->shouldReceive('setSharedSpaceOwner')
            ->with($userId, $invite->sharedSpaceId)
            ->andReturn(5);

        $this->metrics->shouldReceive('sharedSpaceMemberJoined')->once()->with(false, 'some-space', $userId, 5);

        $this->service->join($userId, $sharedSpaceName, $accessCode, $email);
    }

    public function testJoinWhenAlreadyInSharedSpace()
    {
        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->andReturn('my space');
        $this->sharedSpaceRepository->shouldReceive('rollback');

        $this->metrics->shouldReceive('sharedSpaceMemberFailedToJoin')
            ->once()
            ->with('already_in_shared_space', 'my user');
        $this->metrics->shouldNotReceive('sharedSpaceMemberJoined');

        $this->expectException(UserAlreadyInSharedSpaceException::class);
        $this->service->join('my user', 'My Space', '1234', 'a@example.com');
    }

    #[DataProvider('joinFailureProvider')]
    public function testJoinFailureEmitsMetricWithReason(Throwable $exception, string $reason)
    {
        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->andThrow($exception);
        $this->sharedSpaceRepository->shouldReceive('rollback')->once();

        $this->metrics->shouldReceive('sharedSpaceMemberFailedToJoin')
            ->once()
            ->with($reason, 'my user');
        $this->metrics->shouldNotReceive('sharedSpaceMemberJoined');

        $this->expectExceptionObject($exception);
        $this->service->join('my user', 'My Space', '1234', 'a@example.com');
    }

    public static function joinFailureProvider(): array
    {
        return [
            'already in shared space' => [new UserAlreadyInSharedSpaceException('abc'), 'already_in_shared_space'],
            'invite not found' => [new InviteNotFoundException(), 'invite_not_found'],
            'invite not matched' => [new InviteNotMatchedException(), 'invite_not_matched'],
            'invite expired' => [new InviteExpiredException('2024-01-01T00:00:00+00:00'), 'invite_expired'],
            'unexpected error' => [new RuntimeException('boom'), 'error'],
        ];
    }

    public function testDeleteAccount()
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('hasOtherAdmin')
            ->with('xyz', '1')
            ->andReturn(true);
        $this->sharedSpaceRepository
            ->shouldReceive('countMembers')
            ->andReturn(2);
        $this->sharedSpaceRepository
            ->shouldReceive('deleteMember')
            ->with('xyz', '1');
        $this->sharedSpaceRepository
            ->shouldReceive('commit');

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($user);
        $this->userRepository
            ->shouldReceive('delete')
            ->with('1')
            ->andReturn(true);

        $this->logRepository->shouldReceive('addLog')
            ->with(Mockery::on(function ($args): bool {
                return $args['type'] === 'account-deleted'
                    && $args['reason'] === 'User deleted their account';
            }));

        $this->metrics->shouldReceive('userDeleted')->once()->with('user-initiated', '1');
        $this->metrics->shouldReceive('sharedSpaceMemberDeletedBySelf')->once()->with('xyz', '1', false);
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');

        $this->service->deleteAccount('xyz', '1');
    }

    public function testAddMemberEmitsJoinedMetric()
    {
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')->with('new-user')->andReturn(null);
        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('addMember')->with('my-space', 'new-user', true);
        $this->sharedSpaceRepository->shouldReceive('commit');
        $this->applicationRepository->shouldReceive('setSharedSpaceOwner')
            ->with('new-user', 'my-space')
            ->andReturn(3);

        $this->metrics->shouldReceive('sharedSpaceMemberJoined')->once()->with(true, 'my-space', 'new-user', 3, 'admin-user');

        $this->service->addMember('my-space', 'new-user', 'admin-user', true);
    }

    public function testDeleteAccountWhenLastMember()
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('hasOtherAdmin')
            ->andReturn(false);
        $this->sharedSpaceRepository
            ->shouldReceive('countMembers')
            ->andReturn(1);
        $this->sharedSpaceRepository
            ->shouldReceive('deleteSharedSpace')
            ->with('xyz');
        $this->sharedSpaceRepository
            ->shouldReceive('deleteMember')
            ->with('xyz', '1');
        $this->sharedSpaceRepository
            ->shouldReceive('commit');

        $this->applicationRepository
            ->shouldReceive('deleteAllForSharedSpace')
            ->with('xyz')
            ->andReturn(5);

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($user);
        $this->userRepository
            ->shouldReceive('delete')
            ->with('1')
            ->andReturn(true);

        $this->metrics->shouldReceive('userDeleted')->once()->with('user-initiated', '1');
        $this->metrics->shouldReceive('sharedSpaceDeleted')->once()->with('xyz', '1', 5);
        $this->metrics->shouldReceive('sharedSpaceMemberDeletedBySelf')->once()->with('xyz', '1', true);

        $this->logRepository->shouldReceive('addLog')
            ->with(Mockery::on(function ($args): bool {
                return $args['type'] === 'account-deleted'
                    && $args['reason'] === 'User deleted their account';
            }));

        $this->service->deleteAccount('xyz', '1');
    }

    public function testDeleteAccountWhenUserNotFound()
    {
        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('rollback');

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn(null);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to delete shared space member account: User not found', [
                'shared_space_id' => 'xyz',
            ]);

        $this->metrics->shouldNotReceive('userDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedBySelf');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedByAdmin');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not found');
        $this->service->deleteAccount('xyz', '1');
    }

    public function testDeleteAccountWhenNoMembersFound()
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('hasOtherAdmin')
            ->andReturn(false);
        $this->sharedSpaceRepository
            ->shouldReceive('countMembers')
            ->andReturn(0);
        $this->sharedSpaceRepository
            ->shouldReceive('rollback');

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($user);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to delete shared space member account: No members found in shared space', [
                'shared_space_id' => 'xyz',
            ]);

        $this->metrics->shouldNotReceive('userDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedBySelf');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedByAdmin');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No members found in shared space');
        $this->service->deleteAccount('xyz', '1');
    }

    public function testDeleteAccountWhenLastAdmin()
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('countMembers')
            ->andReturn(2);
        $this->sharedSpaceRepository
            ->shouldReceive('hasOtherAdmin')
            ->andReturn(false);
        $this->sharedSpaceRepository
            ->shouldReceive('rollback');

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($user);

        $this->metrics->shouldNotReceive('userDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedBySelf');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedByAdmin');

        $this->expectExceptionMessage('No other admin');
        $this->expectException(NoOtherAdminException::class);
        $this->service->deleteAccount('xyz', '1');
    }

    public function testDeleteAccountWhenUserNotDeleted()
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('hasOtherAdmin')
            ->andReturn(true);
        $this->sharedSpaceRepository
            ->shouldReceive('countMembers')
            ->andReturn(2);
        $this->sharedSpaceRepository
            ->shouldReceive('deleteMember')
            ->with('xyz', '1');
        $this->sharedSpaceRepository
            ->shouldReceive('rollback');

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($user);
        $this->userRepository
            ->shouldReceive('delete')
            ->with('1')
            ->andReturn(false);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to delete shared space member account: User not deleted', [
                'shared_space_id' => 'xyz',
            ]);

        $this->metrics->shouldNotReceive('userDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedBySelf');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedByAdmin');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not deleted');
        $this->service->deleteAccount('xyz', '1');
    }

    public function testDeleteAccountWhenLastMemberAndUserNotDeleted()
    {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('username')->andReturn('xyz');

        $this->sharedSpaceRepository
            ->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository
            ->shouldReceive('hasOtherAdmin')
            ->andReturn(true);
        $this->sharedSpaceRepository
            ->shouldReceive('countMembers')
            ->andReturn(1);
        $this->sharedSpaceRepository
            ->shouldReceive('deleteMember')
            ->with('xyz', '1');
        $this->sharedSpaceRepository
            ->shouldReceive('deleteSharedSpace')
            ->with('xyz');
        $this->sharedSpaceRepository
            ->shouldReceive('rollback');

        $this->applicationRepository
            ->shouldReceive('deleteAllForSharedSpace')
            ->with('xyz')
            ->andReturn(5);

        $this->userRepository
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($user);
        $this->userRepository
            ->shouldReceive('delete')
            ->with('1')
            ->andReturn(false);

        $this->logger
            ->shouldReceive('error')
            ->with('Unable to delete shared space member account: User not deleted', [
                'shared_space_id' => 'xyz',
            ]);

        $this->metrics->shouldNotReceive('userDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceDeleted');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedBySelf');
        $this->metrics->shouldNotReceive('sharedSpaceMemberDeletedByAdmin');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not deleted');
        $this->service->deleteAccount('xyz', '1');
    }

    public function testMatchSharedSpaces()
    {
        $query = 'the space';
        $options = ['offset' => 0, 'limit' => 20];
        $expected = [
            'results' => [['sharedSpaceId' => 'ss1', 'sharedSpaceName' => 'The Space']],
            'total' => 1,
        ];

        $this->sharedSpaceRepository
            ->shouldReceive('matchSharedSpaces')
            ->with($query, $options)
            ->andReturn($expected)
            ->once();

        $result = $this->service->matchSharedSpaces($query, $options);

        $this->assertEquals($expected, $result);
    }


    public function testHasExactlyOneActiveAdmin(): void
    {
        foreach ([true, false] as $expected) {
            $this->sharedSpaceRepository
                ->shouldReceive('hasExactlyOneActiveAdmin')
                ->with('shared-space-1')
                ->once()
                ->andReturn($expected);

            $this->assertSame($expected, $this->service->hasExactlyOneActiveAdmin('shared-space-1'));
        }
    }
}
