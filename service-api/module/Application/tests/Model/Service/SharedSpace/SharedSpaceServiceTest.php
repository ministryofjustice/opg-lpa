<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\SharedSpace;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Entity\MemberInvite;
use Application\Model\Service\SharedSpace\InviteNotFoundException;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use DateTime;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SharedSpaceServiceTest extends MockeryTestCase
{
    private MockInterface|SharedSpaceRepositoryInterface $sharedSpaceRepository;
    private MockInterface|ApplicationRepositoryInterface $applicationRepository;
    private MockInterface|UserRepositoryInterface $userRepository;
    private MockInterface|LogRepositoryInterface $logRepository;
    private MockInterface|LoggerInterface $logger;
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
        $this->logger->shouldReceive('error')->byDefault();

        $this->service = new SharedSpaceService(
            $this->sharedSpaceRepository,
            $this->applicationRepository,
            $this->userRepository,
            $this->logRepository,
            $this->logger,
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

    public function testGetMembers()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getMembers')
            ->with($sharedSpaceId)
            ->andReturn([
                new SharedSpaceMember(['sharedSpaceId' => $sharedSpaceId, 'userId' => 'user1', 'isAdmin' => true, 'isActive' => true]),
                new SharedSpaceMember(['sharedSpaceId' => $sharedSpaceId, 'userId' => 'user2', 'isAdmin' => false, 'isActive' => true]),
                new SharedSpaceMember(['sharedSpaceId' => $sharedSpaceId, 'userId' => 'user3', 'isAdmin' => true, 'isActive' => false]),
            ]);

        $this->userRepository->shouldReceive('getProfiles')
            ->with(['user1', 'user2', 'user3'])
            ->andReturn([
                new User([
                    'id' => 'user1',
                    'name' => ['first' => 'me'],
                    'email' => ['address' => '1@example.com'],
                    'lastLoginAt' => new DateTime('2020-01-01'),
                ]),
                new User([
                    'id' => 'user2',
                    'name' => ['first' => 'you'],
                    'email' => ['address' => '2@example.com'],
                    'lastLoginAt' => new DateTime('2020-01-02'),
                ]),
                new User([
                    'id' => 'user3',
                    'name' => ['first' => 'them'],
                    'email' => ['address' => '3@example.com'],
                    'lastLoginAt' => new DateTime('2020-01-03'),
                ]),
            ]);

        $result = $this->service->getMembers($sharedSpaceId);

        $this->assertEquals([
            [
                'id' => 'user1',
                'name' => new Name(['first' => 'me']),
                'email' => new EmailAddress(['address' => '1@example.com']),
                'lastLoginAt' => new DateTime('2020-01-01'),
                'isActive' => true,
                'isAdmin' => true,
            ],
            [
                'id' => 'user2',
                'name' => new Name(['first' => 'you']),
                'email' => new EmailAddress(['address' => '2@example.com']),
                'lastLoginAt' => new DateTime('2020-01-02'),
                'isActive' => true,
                'isAdmin' => false,
            ],
            [
                'id' => 'user3',
                'name' => new Name(['first' => 'them']),
                'email' => new EmailAddress(['address' => '3@example.com']),
                'lastLoginAt' => new DateTime('2020-01-03'),
                'isActive' => false,
                'isAdmin' => true,
            ],
        ], $result);
    }

    public function testGetMember()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getMember')
            ->with($sharedSpaceId, 'user2')
            ->andReturn(
                new SharedSpaceMember(['sharedSpaceId' => $sharedSpaceId, 'userId' => 'user2', 'isAdmin' => false, 'isActive' => true])
            );

        $this->userRepository->shouldReceive('getProfiles')
            ->with(['user2'])
            ->andReturn([
                new User([
                    'id' => 'user2',
                    'name' => ['first' => 'you'],
                    'email' => ['address' => '2@example.com'],
                    'lastLoginAt' => new DateTime('2020-01-02'),
                ]),
            ]);

        $result = $this->service->getMember($sharedSpaceId, 'user2');

        $this->assertEquals([
            'id' => 'user2',
            'name' => new Name(['first' => 'you']),
            'email' => new EmailAddress(['address' => '2@example.com']),
            'lastLoginAt' => new DateTime('2020-01-02'),
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

        $this->userRepository->shouldNotReceive('getProfiles');

        $result = $this->service->getMember($sharedSpaceId, 'unknown-user');

        $this->assertNull($result);
    }

    public function testUpdateMemberIsAdmin()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('updateMember')
            ->with($sharedSpaceId, $userId, true, false)
            ->once();

        $this->service->updateMember($sharedSpaceId, $userId, true, false);

        $this->addToAssertionCount(1);
    }

    public function testUpdateMemberIsAdminThrowsWhenMemberNotFound()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('updateMember')
            ->with($sharedSpaceId, $userId, true, false)
            ->once()
            ->andThrow(new MemberNotInSharedSpaceException());

        $this->expectException(MemberNotInSharedSpaceException::class);

        $this->service->updateMember($sharedSpaceId, $userId, true, false);
    }

    public function testUpdateMemberIsAdminRethrowsException()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('updateMember')
            ->with($sharedSpaceId, $userId, true, false)
            ->once()
            ->andThrow(new RuntimeException('boom'));

        $this->expectException(RuntimeException::class);

        $this->service->updateMember($sharedSpaceId, $userId, true, false);
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

    #[DoesNotPerformAssertions]
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

        $this->assertEquals([
            [
                'fullName' => 'a b',
                'email' => 'c',
                'isExpired' => false,
                'id' => 1,
            ],
            [
                'fullName' => 'd e',
                'email' => 'f',
                'isExpired' => true,
                'id' => 2,
            ],
        ], $result);
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
            expires: new DateTime('-1 minute'),
        );

        $this->sharedSpaceRepository->shouldReceive('getSharedSpace')
            ->with($memberInvite->sharedSpaceId)
            ->andReturn('my space');

        $this->sharedSpaceRepository->shouldReceive('createInvite')
            ->with($memberInvite)
            ->andReturn(1234);

        $result = $this->service->invite($memberInvite);

        $this->assertEquals([
            'id' => 1234,
            'sharedSpaceName' => 'my space',
            'inviteCode' => $memberInvite->code,
        ], $result);
    }

    #[DoesNotPerformAssertions]
    public function testRevokeInvite()
    {
        $sharedSpaceId = 'space-id';
        $inviteId = 5;
        $revokedByUserId = 'user-id';

        $this->sharedSpaceRepository->shouldReceive('deleteInvite')
            ->with(5);

        $this->logger->shouldReceive('info')
            ->with(
                'Shared space invite revoked',
                [
                    'event'           => 'shared_space.invite_revoked',
                    'shared_space_id' => $sharedSpaceId,
                    'invite_id'       => $inviteId,
                    'revoked_by'      => $revokedByUserId,
                ]
            );

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

    #[DoesNotPerformAssertions]
    public function testJoin()
    {
        $userId = 'my user';
        $sharedSpaceName = 'My Space';
        $accessCode = '1234';

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

        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->with($userId)
            ->andReturn(null);
        $this->sharedSpaceRepository->shouldReceive('getInviteByCodeAndSharedSpaceName')
            ->with($accessCode, $sharedSpaceName)
            ->andReturn($invite);
        $this->sharedSpaceRepository->shouldReceive('addMember')
            ->with($invite->sharedSpaceId, $userId, $invite->isAdmin);
        $this->sharedSpaceRepository->shouldReceive('deleteInvite')
            ->with($invite->id);
        $this->sharedSpaceRepository->shouldReceive('commit');

        $this->applicationRepository->shouldReceive('setSharedSpaceOwner')
            ->with($userId, $invite->sharedSpaceId)
            ->andReturn(5);

        $this->service->join($userId, $sharedSpaceName, $accessCode);
    }

    public function testJoinWhenAlreadyInSharedSpace()
    {
        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->andReturn('my space');
        $this->sharedSpaceRepository->shouldReceive('rollback');

        $this->expectException(UserAlreadyInSharedSpaceException::class);
        $this->service->join('my user', 'My Space', '1234');
    }

    public function testJoinWhenInviteNotFound()
    {
        $this->sharedSpaceRepository->shouldReceive('beginTransaction');
        $this->sharedSpaceRepository->shouldReceive('getSharedSpaceIdForUser')
            ->andReturn(null);
        $this->sharedSpaceRepository->shouldReceive('getInviteByCodeAndSharedSpaceName')
            ->andReturn(null);
        $this->sharedSpaceRepository->shouldReceive('rollback');

        $this->expectException(InviteNotFoundException::class);
        $this->service->join('my user', 'My Space', '1234');
    }
}
