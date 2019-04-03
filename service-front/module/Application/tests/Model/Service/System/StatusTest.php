<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\System\Status;
use Mockery;
use Mockery\MockInterface;

class StatusTest
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

    // TODO - Test with all successes and add other tests once the DynamoClient is being injected LPA-3074
//    public function testCheck() : void
//    {
//        $this->apiClient->shouldReceive('httpGet')->withArgs(['/ping'])->once()->andReturn(['ok' => true]);
//
//        $result = $this->service->check();
//
//        $this->assertEquals([
//            'dynamo' => [
//                'ok' => false,
//                'details' => [
//                    'sessions' => false,
//                    'locks' => false
//                ]
//            ],
//            'api' => [
//                'ok' => true,
//                'details' => [
//                    200 => true,
//                    'ok' => true
//                ]
//            ],
//            'ok' => false,
//            'iterations' => 1
//        ], $result);
//    }
}
