<?php

declare(strict_types=1);

namespace App\Service\SharedSpace;

use App\Service\ApiClient\Client;

class SharedSpaceService
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function create(string $sharedSpaceName): bool
    {
        try {
            /** @var array<string, mixed>|null $result */
            $result = $this->client->httpPost(
                '/v2/shared-space/create',
                ['name' => $sharedSpaceName],
            );
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
