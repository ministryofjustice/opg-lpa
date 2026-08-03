<?php

declare(strict_types=1);

namespace App\Service\SharedSpace;

use App\Service\ApiClient\Client;
use Psr\Log\LoggerInterface;

class SharedSpaceService
{
    public function __construct(
        private readonly Client $client,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Creates a shared space for the current user, returning its id.
     *
     * @return string|null The new shared space's id, or null on failure.
     */
    public function create(string $sharedSpaceName): ?string
    {
        try {
            /** @var array<string, mixed>|null $result */
            $result = $this->client->httpPost(
                '/v2/shared-space/create',
                ['name' => $sharedSpaceName],
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Shared space creation failed', [
                'exception' => $e,
            ]);

            return null;
        }

        return $result['sharedSpaceId'] ?? null;
    }

    public function getMembers(): mixed
    {
        try {
            $result = $this->client->httpGet('/v2/shared-space/members');
        } catch (\Throwable $e) {
            $this->logger->error('Retrieve members of shared space failed', [
                'exception' => $e,
            ]);

            return null;
        }

        return $result;
    }
}
