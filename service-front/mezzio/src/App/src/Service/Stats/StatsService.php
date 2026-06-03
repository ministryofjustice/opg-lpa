<?php

declare(strict_types=1);

namespace App\Service\Stats;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\ApiClient\Exception\ApiException;

class StatsService
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {
    }

    public function getApiStats(): array|false
    {
        try {
            return $this->apiClient->httpGet('/stats/all');
        } catch (ApiException) {
        }

        return false;
    }
}
