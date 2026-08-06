<?php

declare(strict_types=1);

namespace AppTest\Service\SharedSpace;

use App\Service\ApiClient\Client;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Service\SharedSpace\SharedSpaceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SharedSpaceServiceTest extends TestCase
{
    private MockObject&Client $client;
    private MockObject&LoggerInterface $logger;
    private MockObject&MailTransportInterface $mailTransport;
    private SharedSpaceService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailTransport = $this->createMock(MailTransportInterface::class);

        $this->service = new SharedSpaceService($this->client, $this->mailTransport, $this->logger);
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

    public function testGetMemberReturnsMemberOnSuccess(): void
    {
        $this->client->expects($this->once())
            ->method('httpGet')
            ->with('/v2/shared-space/members/user-1')
            ->willReturn(['member' => ['id' => 'user-1', 'isAdmin' => true]]);

        $result = $this->service->getMember('user-1');

        $this->assertSame(['id' => 'user-1', 'isAdmin' => true], $result);
    }

    public function testGetMemberReturnsNullWhenResponseMissingMemberKey(): void
    {
        $this->client->method('httpGet')->willReturn(['success' => true]);

        $result = $this->service->getMember('user-1');

        $this->assertNull($result);
    }

    public function testGetMemberReturnsNullOnAnyException(): void
    {
        $this->client->method('httpGet')->willThrowException(new \RuntimeException('api-error'));

        $result = $this->service->getMember('user-1');

        $this->assertNull($result);
    }

    public function testUpdateMemberIsAdminReturnsTrueOnSuccess(): void
    {
        $this->client->expects($this->once())
            ->method('httpPatch')
            ->with('/v2/shared-space/members/user-1', ['isAdmin' => true, 'isActive' => true])
            ->willReturn(['success' => true]);

        $result = $this->service->updateMember('user-1', true, true);

        $this->assertTrue($result);
    }

    public function testUpdateMemberIsAdminReturnsFalseOnAnyException(): void
    {
        $this->client->method('httpPatch')->willThrowException(new \RuntimeException('api-error'));

        $result = $this->service->updateMember('user-1', true, true);

        $this->assertFalse($result);
    }

    public function testGetMembersAndInvites(): void
    {
        $this->client->expects($this->once())
            ->method('httpGet')
            ->with('/v2/shared-space/members-and-invites')
            ->willReturn(['a' => 'b']);

        $result = $this->service->getMembersAndInvites();

        $this->assertEquals(['a' => 'b'], $result);
    }

    public function testInvite(): void
    {
        $this->client->expects($this->once())
            ->method('httpPost')
            ->with('/v2/shared-space/invite', [
                'firstNames' => 'a',
                'lastName' => 'b',
                'email' => 'you@example.com',
                'isAdmin' => true,
            ])
            ->willReturn(['sharedSpaceName' => 'my space', 'inviteCode' => '12341234']);

        $this->mailTransport->expects($this->once())
            ->method('send')
            ->with(new MailParameters('you@example.com', SharedSpaceService::EMAIL_INVITE_MEMBER, [
                'inviteeFullName' => 'a b',
                'inviterEmail' => 'me@example.com',
                'sharedSpaceName' => 'my space',
                'inviteCode' => '12341234',
            ]));

        $result = $this->service->invite('me@example.com', 'a', 'b', 'you@example.com', true);

        $this->assertTrue($result);
    }
}
