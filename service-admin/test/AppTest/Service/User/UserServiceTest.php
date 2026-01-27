<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\User\UserService;
use DateTime;
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

    public function testSearchForUserByEmail()
    {
        $id = 'fooman';
        $email = 'foo@bar';
        $numLpas = 10;

        $client = $this->prophesize(ApiClient::class);

        // initial search
        $query = ['email' => $email];
        $client->httpGet('/v2/users/search', $query)->willReturn([
            'userId' => $id,
            'isActive' => true
        ]);

        // lpa lookup
        $query = ["page" => 1, "perPage" => 1];
        $client->httpGet(sprintf('/v2/user/%s/applications', $id), $query)->willReturn([
            'total' => $numLpas
        ]);

        $userService = new UserService($client->reveal());
        $userService->setLogger($this->logger->reveal());
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

        // initial search; default limit and offset 0
        $params = [
            'query' => $query,
            'offset' => 0,
            'limit' => 10,
        ];

        $client->httpGet('/v2/users/match', $params)->willReturn([[
            'userId' => $id,
            'isActive' => true,
            'numberOfLpas' => $numLpas,
            'activatedAt' => [
                'date' => '2020-01-21T15:16:02.000000+0000',
                'timezone' => 'Europe/London',
            ],
        ]]);

        // match method on service
        $userService = new UserService($client->reveal());
        $userService->setLogger($this->logger->reveal());
        $actual = $userService->match($params);

        $this->assertEquals($id, $actual[0]['userId']);
        $this->assertEquals(true, $actual[0]['isActive']);
        $this->assertEquals($numLpas, $actual[0]['numberOfLpas']);
        $this->assertInstanceOf(DateTime::class, $actual[0]['activatedAt']);
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

        $userService = new UserService($client->reveal());
        $userService->setLogger($this->logger->reveal());
        $actual = $userService->userLpas($userId);

        $this->assertEquals($expectedLpas, $actual);
    }

    public function testUserLpasReturnsFalseWhenNoApplicationsKey()
    {
        $userId = '123';

        $client = $this->prophesize(ApiClient::class);

        $query = ['page' => 1, 'perPage' => 20];
        $client->httpGet(sprintf('/v2/user/%s/applications', $userId), $query)->willReturn([
            'total' => 0,
        ]);

        $userService = new UserService($client->reveal());
        $userService->setLogger($this->logger->reveal());
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

        $userService = new UserService($client->reveal());
        $userService->setLogger($this->logger->reveal());
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

        $userService = new UserService($client->reveal());
        $userService->setLogger($this->logger->reveal());
        $actual = $userService->userLpas($userId);

        $this->assertEquals([], $actual);
    }
}
