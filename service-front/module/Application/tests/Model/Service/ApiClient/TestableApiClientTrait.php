<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\ApiClient;

use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Client;

class TestableApiClientTrait
{
    use ApiClientTrait;

    public function getApiClient(): Client
    {
        return $this->apiClient;
    }
}
