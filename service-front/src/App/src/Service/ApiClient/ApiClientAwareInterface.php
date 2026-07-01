<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Service\ApiClient\Client;

interface ApiClientAwareInterface
{
    public function setApiClient(Client $apiClient): static;
}
