<?php

namespace App\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use MakeShared\DataModel\User\User;
use DateTime;
use DateTimeZone;
use Exception;

class UserService
{
    /**
     * @var ApiClient
     */
    private $client;

    /**
     * UserService constructor
     *
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $id
     * @return null|User
     */
    public function fetch($id)
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
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
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

    /**
     * @param string $email
     * @return array<string, mixed>|bool
     */
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

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function match(array $params)
    {
        $users = $this->client->httpGet('/v2/users/match', $params);
        return array_map(fn ($user) => $this->convertDates($user), $users);
    }
}
