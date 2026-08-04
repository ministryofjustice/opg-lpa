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

    public function addMember(string $sharedSpaceId, string $userIdToAdd): bool
    {
        try {
            $this->client->httpPost(
                '/v2/shared-space/members',
                ['sharedSpaceId' => $sharedSpaceId, 'userIdToAdd' => $userIdToAdd]
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Adding member to shared space failed', [
                'exception' => $e,
                'sharedSpaceId' => $sharedSpaceId,
                'userIdToAdd' => $userIdToAdd,
            ]);

            return false;
        }

        return true;
    }

    public function updateMemberIsAdmin(string $memberUserId, bool $isAdmin): bool
    {
        try {
            $this->client->httpPatch(
                '/v2/shared-space/members/' . $memberUserId,
                ['isAdmin' => $isAdmin],
            );
        } catch (\Throwable $e) {
            $this->logger->error('Updating shared space member failed', [
                'exception' => $e,
                'memberUserId' => $memberUserId,
                'isAdmin' => $isAdmin,
            ]);

            return false;
        }

        return true;
    }
}
