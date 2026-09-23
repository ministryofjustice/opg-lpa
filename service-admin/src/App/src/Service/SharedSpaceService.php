<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Service\ApiClient\Client as ApiClient;

class SharedSpaceService
{
    public function __construct(private ApiClient $client, private LoggerInterface $logger)
    {
    }

    /**
     * @return array{results: array, total: int}|false
     */
    public function matchSharedSpaces(string $fullOrPartialName, int $page, int $perPage): bool|array
    {
        $offset = ($page - 1) * $perPage;

        try {
            $response = $this->client->httpGet('/v2/admin/match-shared-spaces', array_merge(
                ['fullOrPartialName' => $fullOrPartialName],
                ['offset' => $offset, 'limit' => $perPage]
            ));

            if (is_array($response) && isset($response['results']) && is_array($response['results'])) {
                return [
                    'results' => $response['results'],
                    'total' => intval($response['total'] ?? 0),
                ];
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Match shared spaces failed', [
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * @return array{sharedSpaceName: string, members: array, membersTotal: int, invites: array, invitesTotal: int}|false
     */
    public function members(
        string $sharedSpaceId,
        int $membersPage,
        int $membersPerPage,
        int $invitesPage,
        int $invitesPerPage,
    ): bool|array {
        try {
            $response = $this->client->httpGet(sprintf('/v2/admin/shared-space/%s/members', $sharedSpaceId), [
                'membersPage' => $membersPage,
                'membersPerPage' => $membersPerPage,
                'invitesPage' => $invitesPage,
                'invitesPerPage' => $invitesPerPage,
            ]);

            if (
                is_array($response)
                && isset($response['sharedSpaceName'])
                && isset($response['members']) && is_array($response['members'])
                && isset($response['invites']) && is_array($response['invites'])
            ) {
                return [
                    'sharedSpaceName' => $response['sharedSpaceName'],
                    'members' => $response['members'],
                    'membersTotal' => intval($response['membersTotal'] ?? 0),
                    'invites' => $response['invites'],
                    'invitesTotal' => intval($response['invitesTotal'] ?? 0),
                ];
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Get shared space members failed', [
                'exception' => $e,
            ]);
            return false;
        }
    }
}
