<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\SharedSpace;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Entity\MemberInvite;
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
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SharedSpaceServiceTest extends MockeryTestCase
{
    private MockInterface|SharedSpaceRepositoryInterface $sharedSpaceRepository;
    private MockInterface|ApplicationRepositoryInterface $applicationRepository;
    private MockInterface|UserRepositoryInterface $userRepository;
    private MockInterface|LoggerInterface $logger;
    private SharedSpaceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedSpaceRepository = Mockery::mock(SharedSpaceRepositoryInterface::class);
        $this->applicationRepository = Mockery::mock(ApplicationRepositoryInterface::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info')->byDefault();
        $this->logger->shouldReceive('error')->byDefault();

        $this->service = new SharedSpaceService(
            $this->sharedSpaceRepository,
            $this->applicationRepository,
            $this->userRepository,
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

        $this->sharedSpaceRepository->shouldReceive('updateMemberIsAdmin')
            ->with($sharedSpaceId, $userId, true)
            ->once();

        $this->service->updateMemberIsAdmin($sharedSpaceId, $userId, true);

        $this->addToAssertionCount(1);
    }

    public function testUpdateMemberIsAdminThrowsWhenMemberNotFound()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('updateMemberIsAdmin')
            ->with($sharedSpaceId, $userId, true)
            ->once()
            ->andThrow(new MemberNotInSharedSpaceException());

        $this->expectException(MemberNotInSharedSpaceException::class);

        $this->service->updateMemberIsAdmin($sharedSpaceId, $userId, true);
    }

    public function testUpdateMemberIsAdminRethrowsException()
    {
        $sharedSpaceId = 'my-space';
        $userId = 'user1';

        $this->sharedSpaceRepository->shouldReceive('updateMemberIsAdmin')
            ->with($sharedSpaceId, $userId, true)
            ->once()
            ->andThrow(new RuntimeException('boom'));

        $this->expectException(RuntimeException::class);

        $this->service->updateMemberIsAdmin($sharedSpaceId, $userId, true);
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

    public function testGetInvites()
    {
        $sharedSpaceId = 'my-space';

        $this->sharedSpaceRepository->shouldReceive('getInvites')
            ->with($sharedSpaceId)
            ->andReturn([
                new MemberInvite(
                    firstNames: 'a',
                    lastName: 'b',
                    email: 'c',
                    expires: new DateTime('+1 minute'),
                    userId: '',
                    sharedSpaceId: '',
                    isAdmin: false,
                    code: '',
                    created: new DateTime(),
                ),
                new MemberInvite(
                    firstNames: 'd',
                    lastName: 'e',
                    email: 'f',
                    expires: new DateTime('-1 minute'),
                    userId: '',
                    sharedSpaceId: '',
                    isAdmin: false,
                    code: '',
                    created: new DateTime(),
                ),
            ]);

        $result = $this->service->getInvites($sharedSpaceId);

        $this->assertEquals([
            [
                'fullName' => 'a b',
                'email' => 'c',
                'isExpired' => false,
            ],
            [
                'fullName' => 'd e',
                'email' => 'f',
                'isExpired' => true,
            ],
        ], $result);
    }

    public function testInvite()
    {
        $memberInvite = new MemberInvite(
            firstNames: 'a',
            lastName: 'b',
            email: 'c',
            expires: new DateTime('-1 minute'),
            userId: 'my user',
            sharedSpaceId: 'my space',
            isAdmin: false,
            code: '12341234',
            created: new DateTime(),
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
}
