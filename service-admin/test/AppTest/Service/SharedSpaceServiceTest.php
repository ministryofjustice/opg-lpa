<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\SharedSpaceService;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class SharedSpaceServiceTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $logger;

    public function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public function testMembersReturnsMembersAndInvites(): void
    {
        $client = $this->prophesize(ApiClient::class);

        $apiResponse = [
            'sharedSpaceName' => 'The Space',
            'members' => [
                ['userId' => 'u1', 'name' => ['first' => 'Alice', 'last' => 'A'], 'email' => 'alice@example.com', 'isAdmin' => true],
            ],
            'membersTotal' => 21,
            'invites' => [
                ['id' => 1, 'fullName' => 'Bob B', 'email' => 'bob@example.com', 'createdAt' => '2024-01-01T00:00:00.000000+0000', 'isExpired' => false],
            ],
            'invitesTotal' => 5,
        ];

        $client->httpGet('/v2/admin/shared-space/ss1/members', [
            'membersPage' => 2,
            'membersPerPage' => 20,
            'invitesPage' => 1,
            'invitesPerPage' => 20,
        ])->willReturn($apiResponse);

        $service = new SharedSpaceService($client->reveal(), $this->logger->reveal());

        $result = $service->members('ss1', 2, 20, 1, 20);

        $this->assertEquals([
            'sharedSpaceName' => 'The Space',
            'members' => $apiResponse['members'],
            'membersTotal' => 21,
            'invites' => $apiResponse['invites'],
            'invitesTotal' => 5,
        ], $result);
    }

    public function testMembersReturnsFalseWhenResponseIsInvalid(): void
    {
        $client = $this->prophesize(ApiClient::class);
        $client->httpGet('/v2/admin/shared-space/ss1/members', [
            'membersPage' => 1,
            'membersPerPage' => 20,
            'invitesPage' => 1,
            'invitesPerPage' => 20,
        ])->willReturn(null);

        $service = new SharedSpaceService($client->reveal(), $this->logger->reveal());

        $this->assertFalse($service->members('ss1', 1, 20, 1, 20));
    }

    public function testMembersReturnsFalseOnException(): void
    {
        $client = $this->prophesize(ApiClient::class);
        $client->httpGet('/v2/admin/shared-space/ss1/members', [
            'membersPage' => 1,
            'membersPerPage' => 20,
            'invitesPage' => 1,
            'invitesPerPage' => 20,
        ])->willThrow(new \RuntimeException('boom'));

        $this->logger->error('Get shared space members failed', \Prophecy\Argument::any())->shouldBeCalled();

        $service = new SharedSpaceService($client->reveal(), $this->logger->reveal());

        $this->assertFalse($service->members('ss1', 1, 20, 1, 20));
    }
}
