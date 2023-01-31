<?php

namespace App\Service\Authentication;

use App\Service\ApiClient\ApiException;
use App\Service\ApiClient\Client as ApiClient;

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
     * @param string $email
     * @param string $password
     * @return Result
     */
    public function authenticate(
        #[\SensitiveParameter] string $email,
        #[\SensitiveParameter] string $password
    ) {
        try {
            $userData = $this->client->httpPost('/v2/authenticate', [
                'username' => strtolower($email),
                'password' => $password,
            ]);

            //  If no exception has been thrown then this is OK - transfer the details to the success result
            $identity = new Identity($userData);

            return new Result(Result::SUCCESS, $identity);
        } catch (ApiException $apiEx) {
            $resultCode = Result::FAILURE;

            if ($apiEx->getCode() === 401) {
                $resultCode = Result::FAILURE_CREDENTIAL_INVALID;

                //  Check to see if the account is locked
                if ($apiEx->getMessage() == 'account-locked/max-login-attempts') {
                    $resultCode = Result::FAILURE_ACCOUNT_LOCKED;
                }
            }

            return new Result($resultCode, null, [$apiEx->getMessage()]);
        }
    }

    /**
     * Verify the user with the token value
     *
     * @param string $token
     * @return Result
     */
    public function verify(#[\SensitiveParameter] string $token)
    {
        try {
            $userData = $this->client->httpPost('/v2/authenticate', [
                'authToken' => $token,
            ]);

            //  If no exception has been thrown then this is OK - transfer the details to the success result
            $identity = new Identity($userData);

            return new Result(Result::SUCCESS, $identity);
        } catch (ApiException $apiEx) {
            return new Result(Result::FAILURE, null, [$apiEx->getMessage()]);
        }
    }
}
