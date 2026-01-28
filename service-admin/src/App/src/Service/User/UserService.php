<?php

namespace App\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use MakeShared\DataModel\User\User;
use DateTime;
use DateTimeZone;
use Exception;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class UserService implements LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(private ApiClient $client)
    {
    }

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
        $userData = $this->client->httpGet('/v2/users/search', [
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

    public function userLpas(string $userId): array|bool
    {
        try {
            $lpaData = $this->client->httpGet(sprintf('/v2/user/%s/applications', $userId), [
                'page' => 1,
                'perPage' => 20,
            ]);

            if (is_array($lpaData) && array_key_exists('applications', $lpaData)) {
                $lpas = $lpaData['applications'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->getLogger()->error($e->getMessage());
            return false;
        }

        return $lpas;
    }

    public function match(array $params): array
    {
        $users = $this->client->httpGet('/v2/users/match', $params);
        return array_map(fn ($user) => $this->convertDates($user), $users);
    }
}
