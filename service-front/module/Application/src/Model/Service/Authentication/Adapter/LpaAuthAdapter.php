<?php

namespace Application\Model\Service\Authentication\Adapter;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\ApiClient\Response\AuthResponse;
use Application\Model\Service\Authentication\Identity\User;
use Laminas\Authentication\Adapter\Exception\RuntimeException;
use Laminas\Authentication\Result;
use DateTime;

/**
 * Performs email address & password authentication with the LPA API Client.
 *
 * @package Application\Model\Service\Authentication\Adapter
 */
class LpaAuthAdapter implements AdapterInterface
{
    private $client;
    private $email;
    private $password;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set the email address credential to attempt authentication with.
     *
     * @param $email
     * @return $this
     */
    public function setEmail(#[\SensitiveParameter] $email)
    {
        $this->email = trim(strtolower($email));

        return $this;
    }

    /**
     * Set the password credential to attempt authentication with.
     *
     * @param $password
     * @return $this
     */
    public function setPassword(#[\SensitiveParameter] $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Attempt to authenticate the user with the set credentials, via the LPA API Client.
     *
     * @return Result
     */
    public function authenticate()
    {
        if (!isset($this->email)) {
            throw new RuntimeException('Email address not set');
        }

        if (!isset($this->password)) {
            throw new RuntimeException('Password not set');
        }

        //  Initially assume the authentication failed
        $response = new AuthResponse();
        $response->setErrorDescription('authentication-failed');
        $failureCode = Result::FAILURE_CREDENTIAL_INVALID;

        try {
            $result = $this->client->httpPost('/v2/authenticate', [
                'username' => strtolower($this->email),
                'password' => $this->password,
            ]);

            $response = AuthResponse::buildFromResponse($result);
        } catch (ApiException $ex) {
            if ($ex->getCode() === 500) {
                $response->setErrorDescription('api-error');

                // change failure code so that we can distinguish
                // API errors from credential failures
                $failureCode = Result::FAILURE;
            } else {
                $msg = $ex->getMessage();
                if ($msg === 'account-locked/max-login-attempts') {
                    $response->setErrorDescription('locked');
                } elseif ($msg === 'account-not-active') {
                    $response->setErrorDescription('not-activated');
                }
            }
        }

        // Don't leave this lying around
        unset($this->password);

        if (!$response->isAuthenticated()) {
            return new Result($failureCode, null, [
                $response->getErrorDescription()
            ]);
        }

        $lastLogin = $response->getLastLogin();
        // if response lastLogin is null, this returns now as the datetime
        $lastLogin = $lastLogin ? new DateTime($lastLogin) : new DateTime();
        $identity = new User($response->getUserId(), $response->getToken(), $response->getExpiresIn(), $lastLogin);

        $messages = [];

        //  If inactivity flags were cleared during this authentication then put a message in the result
        if ($response->getInactivityFlagsCleared()) {
            $messages[] = 'inactivity-flags-cleared';
        }

        return new Result(Result::SUCCESS, $identity, $messages);
    }

    /**
     * @param $token string
     * @return array|null|string
     * @throws \Http\Client\Exception
     */
    public function getSessionExpiry(#[\SensitiveParameter] string $token)
    {
        try {
            $result = $this->client->httpGet(
                '/v2/session-expiry',
                [],
                true,
                true,
                ['CheckedToken' => $token]
            );

            return $result;
        } catch (ApiException $ex) {
            return null;
        }
    }

    /**
     * @param $token string
     * @param $expirySeconds int - set expiry this many seconds from now
     * @return array|null|string
     * @throws \Http\Client\Exception
     */
    public function setSessionExpiry(#[\SensitiveParameter] string $token, int $expireInSeconds)
    {
        try {
            $result = $this->client->httpPost(
                '/v2/session-set-expiry',
                ['expireInSeconds' => $expireInSeconds],
                ['CheckedToken' => $token]
            );

            return $result;
        } catch (ApiException $ex) {
            return $ex->getMessage();
        }
    }
}
