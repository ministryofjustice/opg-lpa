<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\ApiClient\Client as ApiClient;
use DateTime;
use DateTimeZone;
use Exception;
use MakeShared\DataModel\User\User;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

class UserService
{
    /**
     * As this class is instantiated via autowiring, psalm doesn't think the
     * constructor is used. Suppress this misunderstanding.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(private ApiClient $client, private LoggerInterface $logger)
    {
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function fetch(string $id): ?User
    {
        $userData = $this->client->httpGet('/v2/user/' . $id);

        if (is_array($userData)) {
            return new User($userData);
        }

        return null;
    }

    /**
     * Convert the date fields for a single user.
     * Returns the user with the modified dates.
     */
    private function convertDates(array $user): array
    {
        //  Parse the datetime fields as required
        $dateFields = [
            'lastLoginAt',
            'updatedAt',
            'createdAt',
            'activatedAt',
            'deletedAt',
        ];

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $user) && isset($user[$dateField])) {
                $user[$dateField] = new DateTime(
                    $user[$dateField]['date'],
                    new DateTimeZone($user[$dateField]['timezone'])
                );
            }
        }

        return $user;
    }

    public function search(#[\SensitiveParameter] string $email)
    {
        $userData = $this->client->httpGet('/v2/admin/search-users', [
            'email' => $email
        ]);

        if (is_array($userData)) {
            $userData = $this->convertDates($userData);

            //  If the user is active retrieve the LPA data
            if (array_key_exists('userId', $userData) && $userData['isActive'] === true) {
                $numberOfLpas = 0;

                try {
                    $lpaData = $this->client->httpGet(sprintf('/v2/user/%s/applications', $userData['userId']), [
                        'page' => 1,
                        'perPage' => 1,
                    ]);

                    if (is_array($lpaData) && array_key_exists('total', $lpaData)) {
                        $numberOfLpas = $lpaData['total'];
                    }
                } catch (Exception $ignore) {
                }

                $userData['numberOfLpas'] = $numberOfLpas;
            }

            return $userData;
        }

        return false;
    }

    public function searchById(string $id): array|false
    {
        try {
            $userData = $this->client->httpGet('/v2/user/' . $id);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        if (!is_array($userData) || !isset($userData['email']['address'])) {
            return false;
        }

        return $this->search($userData['email']['address']);
    }

    public function searchByAReference(string $aReference): array|false
    {
        $userData = $this->client->httpGet('/v2/admin/search-users', [
            'aReference' => $aReference,
        ]);

        if (is_array($userData)) {
            return $this->convertDates($userData);
        }

        return false;
    }

    /**
     * @return array{results: array, total: int}|false
     * @throws ClientExceptionInterface
     */
    public function userLpas(string $userId, int $page = 1, int $perPage = 20): array|false
    {
        try {
            $lpaData = $this->client->httpGet(sprintf('/v2/user/%s/applications', $userId), [
                'page' => $page,
                'perPage' => $perPage,
            ]);

            if (is_array($lpaData) && array_key_exists('applications', $lpaData) && is_array($lpaData['applications'])) {
                return [
                    'results' => $lpaData['applications'],
                    'total' => $lpaData['total'] ?? 0,
                ];
            }

            return false;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * @return array{results: array, total: int}|false
     * @throws ClientExceptionInterface
     */
    public function sharedSpaceLpas(string $sharedSpaceId, int $page = 1, int $perPage = 20): array|false
    {
        try {
            $lpaData = $this->client->httpGet(sprintf('/v2/admin/shared-space/%s/lpas', $sharedSpaceId), [
                'page' => $page,
                'perPage' => $perPage,
            ]);

            if (is_array($lpaData) && array_key_exists('applications', $lpaData) && is_array($lpaData['applications'])) {
                return [
                    'results' => $lpaData['applications'],
                    'total' => $lpaData['total'] ?? 0,
                ];
            }

            return false;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * @param string $fullOrPartialEmail
     * @param int $page
     * @param int $perPage
     * @return array{results: array, total: int}|false
     */
    public function match(string $fullOrPartialEmail, int $page, int $perPage): array|false
    {
        $offset = ($page - 1) * $perPage;

        try {
            $response = $this->client->httpGet('/v2/admin/match-users', [
                'fullOrPartialEmail' => $fullOrPartialEmail,
                'offset' => $offset,
                'limit' => $perPage,
            ]);

            if (!is_array($response) || !isset($response['results']) || !is_array($response['results'])) {
                return false;
            }

            return [
                'results' => array_map(fn ($user) => $this->convertDates($user), $response['results']),
                'total' => intval($response['total'] ?? 0),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Match users failed', [
                'exception' => $e,
            ]);
            return false;
        }
    }
}
