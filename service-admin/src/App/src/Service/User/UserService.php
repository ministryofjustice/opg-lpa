<?php

namespace App\Service\User;

use App\Service\ApiClient\Client as ApiClient;
use Opg\Lpa\DataModel\User\User;
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
     * @param $id
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

    // convert the date fields for a single user
    private function convertDates($user)
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
     * @return array|bool
     */
    public function search(string $email)
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
     * @param array $params
     * @return array|bool
     */
    public function match(array $params)
    {
        $users = $this->client->httpGet('/v2/users/match', $params);
        return array_map(fn ($user) => $this->convertDates($user), $users);
    }
}
