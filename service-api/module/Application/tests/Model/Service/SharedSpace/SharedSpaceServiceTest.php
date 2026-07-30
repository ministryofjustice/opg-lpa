<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\SharedSpace;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use MakeShared\DataModel\User\User;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Common\EmailAddress;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use DateTime;

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
                ['userId' => 'user1'],
                ['userId' => 'user2', 'isActive' => true, 'isAdmin' => false],
                ['userId' => 'user3', 'isActive' => false, 'isAdmin' => true],
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
}
