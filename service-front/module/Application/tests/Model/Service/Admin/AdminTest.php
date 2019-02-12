<?php

namespace ApplicationTest\Model\Service\Admin;

use Application\Model\Service\Admin\Admin;
use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Authentication\AuthenticationService;
use DateTime;
use DateTimeZone;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AdminTest extends MockeryTestCase
{
    /**
     * @var AuthenticationService|MockInterface
     */
    private $authenticationService;

    /**
     * @var Client|MockInterface
     */
    private $apiClient;

    /**
     * @var Admin
     */
    private $service;

    public function setUp() : void
    {
        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $this->service = new Admin($this->authenticationService, []);

        $this->apiClient = Mockery::mock(Client::class);
        $this->service->setApiClient($this->apiClient);
    }

    public function createSearchResponse() : array
    {
        return [
            'lastLoginAt' => ['date' => '2010-01-01 10:00', 'timezone' => 'UTC'],
            'updatedAt' => ['date' => '2010-01-02 10:00', 'timezone' => 'UTC'],
            'createdAt' => ['date' => '2010-01-03 10:00', 'timezone' => 'UTC'],
            'activatedAt' => ['date' => '2010-01-04 10:00', 'timezone' => 'UTC'],
            'deletedAt' => ['date' => '2010-01-05 10:00', 'timezone' => 'UTC'],
            'userId' => 'id',
            'isActive' => true
        ];
    }

    /**
     * @throws Exception
     */
    public function testSearchUsersFoundIsActiveWithLpas() : void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/users/search', ['email' => 'found@email.com']])
            ->andReturn($this->createSearchResponse())
            ->once();

        $applicationsResponse = ['total'=> 10];

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/id/applications', ['page' => 1, 'perPage' => 1]])
            ->andReturn($applicationsResponse)
            ->once();

        $result = $this->service->searchUsers('found@email.com');

        $this->assertEquals([
            'lastLoginAt' => new DateTime('2010-01-01 10:00'),
            'updatedAt' => new DateTime('2010-01-02 10:00'),
            'createdAt' => new DateTime('2010-01-03 10:00'),
            'activatedAt' => new DateTime('2010-01-04 10:00'),
            'deletedAt' => new DateTime('2010-01-05 10:00'),
            'userId' => 'id',
            'isActive' => true,
            'numberOfLpas' => 10
        ], $result);
    }

    /**
     * @throws Exception
     */
    public function testSearchUsersFoundIsActiveNoLpas() : void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/users/search', ['email' => 'found@email.com']])
            ->andReturn($this->createSearchResponse())
            ->once();

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/id/applications', ['page' => 1, 'perPage' => 1]])
            ->andReturn(null)
            ->once();

        $result = $this->service->searchUsers('found@email.com');

        $this->assertEquals([
            'lastLoginAt' => new DateTime('2010-01-01 10:00'),
            'updatedAt' => new DateTime('2010-01-02 10:00'),
            'createdAt' => new DateTime('2010-01-03 10:00'),
            'activatedAt' => new DateTime('2010-01-04 10:00'),
            'deletedAt' => new DateTime('2010-01-05 10:00'),
            'userId' => 'id',
            'isActive' => true,
            'numberOfLpas' => 0
        ], $result);
    }

    /**
     * @throws Exception
     */
    public function testSearchUsersFoundNotActive() : void
    {
        $searchResponse = [
            'lastLoginAt' => ['date' => '2010-01-01 10:00', 'timezone' => 'UTC'],
            'updatedAt' => ['date' => '2010-01-02 10:00', 'timezone' => 'UTC'],
            'createdAt' => ['date' => '2010-01-03 10:00', 'timezone' => 'UTC'],
            'activatedAt' => ['date' => '2010-01-04 10:00', 'timezone' => 'UTC'],
            'deletedAt' => ['date' => '2010-01-05 10:00', 'timezone' => 'UTC'],
            'userId' => 'id',
            'isActive' => false
        ];

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/users/search', ['email' => 'found@email.com']])
            ->andReturn($searchResponse)
            ->once();

        $result = $this->service->searchUsers('found@email.com');

        $this->assertEquals([
            'lastLoginAt' => new DateTime('2010-01-01 10:00', new DateTimeZone('UTC')),
            'updatedAt' => new DateTime('2010-01-02 10:00', new DateTimeZone('UTC')),
            'createdAt' => new DateTime('2010-01-03 10:00', new DateTimeZone('UTC')),
            'activatedAt' => new DateTime('2010-01-04 10:00', new DateTimeZone('UTC')),
            'deletedAt' => new DateTime('2010-01-05 10:00', new DateTimeZone('UTC')),
            'userId' => 'id',
            'isActive' => false
        ], $result);
    }

    public function testSearchUsersNotFound() : void
    {
        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/users/search', ['email' => 'notfound@email.com']])
            ->andReturn(false)
            ->once();

        $result = $this->service->searchUsers('notfound@email.com');

        $this->assertEquals(false, $result);
    }
}
