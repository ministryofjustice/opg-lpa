<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\User\UserService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class UserServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testSearchForUserByEmail()
    {
        $id = 'fooman';
        $email = 'foo@bar';
        $numLpas = 10;

        $client = $this->prophesize(ApiClient::class);

        $var = TRUE;

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
        $actual = $userService->match($params);

        $this->assertEquals($id, $actual[0]['userId']);
        $this->assertEquals(true, $actual[0]['isActive']);
        $this->assertEquals($numLpas, $actual[0]['numberOfLpas']);
        $this->assertInstanceOf(DateTime::class, $actual[0]['activatedAt']);
    }
}
