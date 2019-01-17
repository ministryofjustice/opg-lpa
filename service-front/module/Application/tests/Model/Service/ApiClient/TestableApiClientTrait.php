<?php

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Client;

class TestableApiClientTrait
{
    use ApiClientTrait;

    public function getApiClient() : Client
    {
        return $this->apiClient;
    }
}
