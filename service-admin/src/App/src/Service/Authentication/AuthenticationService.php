<?php

namespace App\Service\Authentication;

use App\Service\ApiClient\ApiException;
use App\Service\ApiClient\Client as ApiClient;
use App\User\User;

/**
 * Class AuthenticationService
 * @package App\Service\Authentication
 */
class AuthenticationService
{
    /**
     * @var ApiClient
     */
    private $client;

    /**
     * AuthenticationService constructor
     *
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Authenticate the credentials via the API
     *
     * @param $email
     * @param $password
     * @return Result
     */
    public function authenticate($email, $password)
    {
        try {
            $userData = $this->client->httpPost('/v2/authenticate', [
                'username' => strtolower($email),
                'password' => $password,
            ]);

            //  If no exception has been thrown then this is OK - transfer the details to the success result
            $user = new User($userData);

            return new Result(Result::SUCCESS, $user);
        } catch (ApiException $apiEx) {
            if ($apiEx->getCode() === 401) {
                return new Result(Result::FAILURE_CREDENTIAL_INVALID, null, [
                    $apiEx->getMessage()
                ]);
            } elseif ($apiEx->getCode() === 403) {
                return new Result(Result::FAILURE_ACCOUNT_LOCKED, null, [
                    $apiEx->getMessage()
                ]);
            }

            return new Result(Result::FAILURE, null, [
                $apiEx->getMessage()
            ]);
        }
    }
}
