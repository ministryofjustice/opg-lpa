<?php

declare(strict_types=1);

namespace App\Service\Stats;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;

class StatsService
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {
    }

    /**
     * @return array|false|null|string
     */
    public function getApiStats(): array|string|false|null|false
    {
        try {
            return $this->apiClient->httpGet('/stats/all');
        } catch (ApiException) {
        }

        return false;
    }
}
