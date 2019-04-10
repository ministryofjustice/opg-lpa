<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\System\Status;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
use Mockery\MockInterface;

class StatusTest extends AbstractServiceTest
{
    /**
     * @var $apiClient Client|MockInterface
     */
    private $apiClient;

    /**
     * @var $service Status
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Status($this->authenticationService, []);
        $this->service->setApiClient($this->apiClient);
    }

    public function testCheck() : void
    {
        $this->apiClient
            ->shouldReceive('httpGet')
            ->withArgs(['/ping'])
            ->times(6)
            ->andReturn([
                'ok' => true,
            ]);

        $result = $this->service->check();

        $this->assertEquals([
            'dynamo' => [
                'ok' => false,
                'details' => [
                    'sessions' => false,
                    'locks' => false
                ]
            ],
            'api' => [
                'ok' => true,
                'details' => [
                    200 => true,
                    'ok' => true
                ]
            ],
            'ok' => true,
            'iterations' => 6
        ], $result);
    }
}
