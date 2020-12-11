<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
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
            'isActive' => TRUE
        ]);

        // lpa lookup
        $query = ["page" => 1, "perPage" => 1];
        $client->httpGet(sprintf('/v2/user/%s/applications', $id), $query)->willReturn([
            'total' => $numLpas
        ]);

        $userService = new UserService($client->reveal());
        $actual = $userService->search($email);

        $this->assertEquals($actual['userId'], $id);
        $this->assertEquals($actual['isActive'], TRUE);
        $this->assertEquals($actual['numberOfLpas'], $numLpas);
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

        $client->httpGet('/v2/users/match', $params)->willReturn([
            'userId' => $id,
            'isActive' => TRUE,
            'numberOfLpas' => $numLpas,
        ]);

        // match method on service
        $userService = new UserService($client->reveal());
        $actual = $userService->match($params);

        $this->assertEquals($actual['userId'], $id);
        $this->assertEquals($actual['isActive'], TRUE);
        $this->assertEquals($actual['numberOfLpas'], $numLpas);
    }
}
