<?php

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\Client;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ApiClientTraitTest extends MockeryTestCase
{
    public function testApiClientTrait() : void
    {
        $client = Mockery::mock(Client::class);

        $apiClient = new TestableApiClientTrait();

        $result = $apiClient->setApiClient($client);

        $this->assertEquals($apiClient, $result);
        $this->assertEquals($client, $result->getApiClient());
    }
}
