<?php

declare(strict_types=1);

namespace AppTest\Service\SharedSpace;

use App\Service\ApiClient\Client;
use App\Service\SharedSpace\SharedSpaceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SharedSpaceServiceTest extends TestCase
{
    private Client&MockObject $client;
    private LoggerInterface&MockObject $logger;
    private SharedSpaceService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new SharedSpaceService($this->client, $this->logger);
    }

    public function testCreateReturnsSharedSpaceIdOnSuccess(): void
    {
        $this->client->expects($this->once())
            ->method('httpPost')
            ->with('/v2/shared-space/create', ['name' => 'My family'])
            ->willReturn([
                'sharedSpaceId' => 'shared-space-1',
                'name'          => 'My family',
                'lpasMoved'     => 2,
            ]);

        $result = $this->service->create('My family');

        $this->assertSame('shared-space-1', $result);
    }

    public function testCreateReturnsNullWhenClientThrows(): void
    {
        $this->client->method('httpPost')->willThrowException(new \RuntimeException('api-error'));

        $result = $this->service->create('My family');

        $this->assertNull($result);
    }

    public function testCreateReturnsNullWhenResponseMissingSharedSpaceId(): void
    {
        $this->client->method('httpPost')->willReturn(['name' => 'My family']);

        $result = $this->service->create('My family');

        $this->assertNull($result);
    }

    public function testAddMemberReturnsTrueOnSuccess(): void
    {
        $this->client->expects($this->once())
            ->method('httpPost')
            ->with('/v2/shared-space/members', ['sharedSpaceId' => 'shared-space-1', 'userIdToAdd' => 'user-1'])
            ->willReturn(['success' => true]);

        $result = $this->service->addMember('shared-space-1', 'user-1');

        $this->assertTrue($result);
    }

    public function testAddMemberReturnsFalseOnAnyException(): void
    {
        $this->client->method('httpPost')->willThrowException(new \RuntimeException('api-error'));

        $result = $this->service->addMember('shared-space-1', 'user-1');

        $this->assertFalse($result);
    }

    public function testUpdateMemberIsAdminReturnsTrueOnSuccess(): void
    {
        $this->client->expects($this->once())
            ->method('httpPatch')
            ->with('/v2/shared-space/members/user-1', ['isAdmin' => true])
            ->willReturn(['success' => true]);

        $result = $this->service->updateMemberIsAdmin('user-1', true);

        $this->assertTrue($result);
    }

    public function testUpdateMemberIsAdminReturnsFalseOnAnyException(): void
    {
        $this->client->method('httpPatch')->willThrowException(new \RuntimeException('api-error'));

        $result = $this->service->updateMemberIsAdmin('user-1', true);

        $this->assertFalse($result);
    }
}
