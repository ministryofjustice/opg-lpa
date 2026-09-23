<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\UserService;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class UserServiceTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $logger;

    public function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public static function userSearchProvider(): array
    {
        return [
            'Lay' => ['A', 'a@example.org', 10, null],
            'Shared space' => ['B', 'a@example.org', 5, null],
        ];
    }

    #[DataProvider('userSearchProvider')]
    public function testSearchForUserByEmail(string $id, string $email, int $numLpas, ?string $sharedSpaceId): void
    {
        $client = $this->prophesize(ApiClient::class);

        // initial search
        $query = ['email' => $email];
        $client->httpGet('/v2/admin/search-users', $query)->willReturn([
            'userId' => $id,
            'isActive' => true
        ]);

        // lpa lookup
        $query = ["page" => 1, "perPage" => 1];
        $client->httpGet(sprintf('/v2/user/%s/applications', $id), $query)->willReturn([
            'total' => $numLpas
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->search($email);

        $this->assertEquals($id, $actual['userId']);
        $this->assertEquals(true, $actual['isActive']);
        $this->assertEquals($numLpas, $actual['numberOfLpas']);
    }

    public function testMatchUsers()
    {
        $query = 'lint';

        $id = 'FFFFFlinstone';
        $numLpas = 3;

        $client = $this->prophesize(ApiClient::class);

        // page 1, perPage 10 -> offset 0, limit 10
        $params = [
            'fullOrPartialEmail' => $query,
            'offset' => 0,
            'limit' => 10,
        ];

        $client->httpGet('/v2/admin/match-users', $params)->willReturn([
            'results' => [[
                'userId' => $id,
                'isActive' => true,
                'numberOfLpas' => $numLpas,
                'activatedAt' => [
                    'date' => '2020-01-21T15:16:02.000000+0000',
                    'timezone' => 'Europe/London',
                ],
            ]],
            'total' => 1,
        ]);

        // match method on service
        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->match($query, 1, 10);

        $this->assertEquals(1, $actual['total']);
        $this->assertEquals($id, $actual['results'][0]['userId']);
        $this->assertEquals(true, $actual['results'][0]['isActive']);
        $this->assertEquals($numLpas, $actual['results'][0]['numberOfLpas']);
        $this->assertInstanceOf(DateTime::class, $actual['results'][0]['activatedAt']);
    }

    public function testMatchUsersConvertsPageToOffset()
    {
        $query = 'lint';

        $client = $this->prophesize(ApiClient::class);

        // page 3, perPage 20 -> offset 40, limit 20
        $params = [
            'fullOrPartialEmail' => $query,
            'offset' => 40,
            'limit' => 20,
        ];

        $client->httpGet('/v2/admin/match-users', $params)->willReturn([
            'results' => [],
            'total' => 41,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->match($query, 3, 20);

        $this->assertEquals(41, $actual['total']);
        $this->assertEquals([], $actual['results']);
    }

    public function testMatchUsersReturnsFalseWhenResponseIsInvalid()
    {
        $client = $this->prophesize(ApiClient::class);
        $client->httpGet('/v2/admin/match-users', [
            'fullOrPartialEmail' => 'lint',
            'offset' => 0,
            'limit' => 10,
        ])->willReturn(null);

        $userService = new UserService($client->reveal(), $this->logger->reveal());

        $this->assertFalse($userService->match('lint', 1, 10));
    }

    public function testMatchUsersReturnsFalseOnException()
    {
        $client = $this->prophesize(ApiClient::class);
        $client->httpGet('/v2/admin/match-users', [
            'fullOrPartialEmail' => 'lint',
            'offset' => 0,
            'limit' => 10,
        ])->willThrow(new \RuntimeException('boom'));

        $this->logger->error('Match users failed', \Prophecy\Argument::any())->shouldBeCalled();

        $userService = new UserService($client->reveal(), $this->logger->reveal());

        $this->assertFalse($userService->match('lint', 1, 10));
    }

    public function testUserLpasReturnsApplications()
    {
        $userId = '123';
        $expectedLpas = [
            ['uId' => 'M-1234-5678-9012', 'donor' => 'John Doe'],
            ['uId' => 'M-9876-5432-1098', 'donor' => 'Jane Smith'],
        ];

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/user/%s/applications', $userId), $query)->willReturn([
            'applications' => $expectedLpas,
            'total' => 2,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->userLpas($userId);

        $this->assertEquals($expectedLpas, $actual['results']);
        $this->assertEquals(2, $actual['total']);
    }

    public function testUserLpasConvertsPageAndPerPage()
    {
        $userId = '123';
        $expectedLpas = [
            ['uId' => 'M-1234-5678-9012', 'donor' => 'John Doe'],
        ];

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 3, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/user/%s/applications', $userId), $query)->willReturn([
            'applications' => $expectedLpas,
            'total' => 41,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->userLpas($userId, 3, 20);

        $this->assertEquals($expectedLpas, $actual['results']);
        $this->assertEquals(41, $actual['total']);
    }

    public function testUserLpasReturnsFalseWhenNoApplicationsKey()
    {
        $userId = '123';

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/user/%s/applications', $userId), $query)->willReturn([
            'total' => 0,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->userLpas($userId);

        $this->assertFalse($actual);
    }

    public function testUserLpasReturnsFalseOnException()
    {
        $userId = '123';

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/user/%s/applications', $userId), $query)->willThrow(new \Exception('API error'));

        $this->logger->error('API error')->shouldBeCalled();

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->userLpas($userId);

        $this->assertFalse($actual);
    }

    public function testUserLpasReturnsEmptyArrayWhenNoApplications()
    {
        $userId = '123';

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/user/%s/applications', $userId), $query)->willReturn([
            'applications' => [],
            'total' => 0,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->userLpas($userId);

        $this->assertEquals([], $actual['results']);
        $this->assertEquals(0, $actual['total']);
    }

    public function testSharedSpaceLpasReturnsApplications()
    {
        $sharedSpaceId = 'ss-123';
        $expectedLpas = [
            ['uId' => 'M-1234-5678-9012', 'donor' => 'John Doe'],
        ];

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/admin/shared-space/%s/lpas', $sharedSpaceId), $query)->willReturn([
            'applications' => $expectedLpas,
            'total' => 1,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->sharedSpaceLpas($sharedSpaceId);

        $this->assertEquals($expectedLpas, $actual['results']);
        $this->assertEquals(1, $actual['total']);
    }

    public function testSharedSpaceLpasConvertsPageAndPerPage()
    {
        $sharedSpaceId = 'ss-123';
        $expectedLpas = [
            ['uId' => 'M-1234-5678-9012', 'donor' => 'John Doe'],
        ];

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 2, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/admin/shared-space/%s/lpas', $sharedSpaceId), $query)->willReturn([
            'applications' => $expectedLpas,
            'total' => 25,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->sharedSpaceLpas($sharedSpaceId, 2, 20);

        $this->assertEquals($expectedLpas, $actual['results']);
        $this->assertEquals(25, $actual['total']);
    }

    public function testSharedSpaceLpasReturnsFalseOnException()
    {
        $sharedSpaceId = 'ss-123';

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/admin/shared-space/%s/lpas', $sharedSpaceId), $query)->willThrow(new \Exception('API error'));

        $this->logger->error('API error')->shouldBeCalled();

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->sharedSpaceLpas($sharedSpaceId);

        $this->assertFalse($actual);
    }

    public function testSearchByAReference()
    {
        $aReference = 'A-99998888882';
        $userId = 'abc123def456';

        $client = $this->prophesize(ApiClient::class);

        $client->httpGet('/v2/admin/search-users', ['aReference' => $aReference])->willReturn([
            'userId' => $userId,
            'isActive' => true,
        ]);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->searchByAReference($aReference);

        $this->assertIsArray($actual);
        $this->assertEquals($userId, $actual['userId']);
    }

    public function testSearchByAReferenceNotFound()
    {
        $aReference = 'A-00000000000';

        $client = $this->prophesize(ApiClient::class);

        // API returns null (404 → client returns null)
        $client->httpGet('/v2/admin/search-users', ['aReference' => $aReference])->willReturn(null);

        $userService = new UserService($client->reveal(), $this->logger->reveal());
        $actual = $userService->searchByAReference($aReference);

        $this->assertFalse($actual);
    }
}
