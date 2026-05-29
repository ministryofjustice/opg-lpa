<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Service\ApiClient\Client;

trait ApiClientTrait
{
    private Client $apiClient;

    public function setApiClient(Client $apiClient): static
    {
        $this->apiClient = $apiClient;

        return $this;
    }
}
