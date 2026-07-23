<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\SharedSpace;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\SharedSpace\UserAlreadyInSharedSpaceException;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

final class ServiceTest extends AbstractServiceTestCase
{
    private MockInterface|ApplicationRepositoryInterface $applicationRepository;

    private MockInterface|SharedSpaceRepositoryInterface $sharedSpaceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationRepository = Mockery::mock(ApplicationRepositoryInterface::class);
        $this->sharedSpaceRepository = Mockery::mock(SharedSpaceRepositoryInterface::class);
        $this->logger->shouldReceive('log')->byDefault();
    }

    private function createService(): SharedSpaceService
    {
        $service = new SharedSpaceService();
        $service->setApplicationRepository($this->applicationRepository);
        $service->setSharedSpaceRepository($this->sharedSpaceRepository);
        $service->setLogger($this->logger);

        return $service;
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

        $service = $this->createService();

        $this->expectException(UserAlreadyInSharedSpaceException::class);

        $service->create('My Space', $userId);
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

        $service = $this->createService();

        $result = $service->create('My Space', $userId);

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

        $service = $this->createService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create shared space');

        $service->create('My Space', $userId);
    }
}
