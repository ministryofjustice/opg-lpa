<?php

namespace Application\Model\Service\AuthClient;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle5\Client as Guzzle5Client;
use Http\Client\HttpClient as HttpClientInterface;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $authBaseUri;

    /**
     * @var string
     */
    private $token;

    /**
     * The user ID of the logged in account
     *
     * @var string
     */
    private $userId;

    /**
     * Client constructor
     *
     * @param $authBaseUri
     */
    public function __construct($authBaseUri)
    {
        $this->authBaseUri = $authBaseUri;
    }


    // Internal API access methods

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'LPA-FRONT'
        ];

        if ($this->getToken() != null) {
            $headers['Token'] = $this->getToken();
        }

        return $headers;
    }

    /**
     * @param Uri $url
     * @param array $query
     * @return ResponseInterface
     */
    private function httpGet(Uri $url, array $query = array())
    {
        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, urlencode($value));
        }

        $request = new Request('GET', $url, $this->buildHeaders(), '{}');

        try {
            $response = $this->getHttpClient()->sendRequest($request);
//            $this->lastStatusCode = $response->getStatusCode();
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 404])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    private function httpPost(Uri $url, array $payload = array())
    {
        $body = (!empty($payload) ? json_encode($payload) : null);
        $request = new Request('POST', $url, $this->buildHeaders(), $body);

        try {
            $response = $this->getHttpClient()->sendRequest($request);
//            $this->lastStatusCode = $response->getStatusCode();
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 201, 204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    private function httpPatch(Uri $url, array $payload)
    {
        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->getHttpClient()->sendRequest($request);
//            $this->lastStatusCode = $response->getStatusCode();
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() != 200) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    private function httpDelete(Uri $url)
    {
        $request = new Request('DELETE', $url, $this->buildHeaders(), '{}');

        try {
            $response = $this->getHttpClient()->sendRequest($request);
//            $this->lastStatusCode = $response->getStatusCode();
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    // Response Handling

    /**
     * Called with a response from the API when the response code was unsuccessful. i.e. not 20X.
     *
     * @param ResponseInterface $response
     *
     * @return Exception\ResponseException
     */
    protected function createErrorException(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        $message = "HTTP:{$response->getStatusCode()} - ";
        $message .= (is_array($body)) ? print_r($body, true) : 'Unexpected response from server';

        return new Exception\ResponseException($message, $response->getStatusCode(), $response);
    }

    // Getters and setters

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return Exception\ResponseException|Response\Error|array|bool|string
     * @throws \Exception
     */
    public function getUserId()
    {
        if (is_null($this->userId)) {
            $response = $this->getTokenInfo($this->getToken());

            if ($response instanceof Response\ErrorInterface) {
                if ($response instanceof \Exception) {
                    throw $response;
                }

                return $response;
            }

            if (!is_array($response) || !isset($response['userId'])) {
                return false;
            }

            $this->setUserId($response['userId']);
        }

        return $this->userId;
    }

    /**
     * @return string
     */
    final public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token string
     * @return $this
     */
    final public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return HttpClientInterface
     */
    final protected function getHttpClient()
    {
        if (!$this->httpClient instanceof HttpClientInterface) {
            // @todo - For now create this using Guzzle v5.
            // This should be removed when the tight couple to Guzzle v5 is removed.

            $this->httpClient = new Guzzle5Client(new GuzzleHttpClient(), new GuzzleMessageFactory());
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $client
     */
    final protected function setHttpClient(HttpClientInterface $client)
    {
        $this->httpClient = $client;
    }









    // Public Auth access methods

    /**
     * Authenticate the user with an email address and password.
     *
     * @param $email
     * @param $password
     * @return Response\AuthResponse
     */
    public function authenticate($email, $password)
    {
        $url = new Uri($this->authBaseUri . '/v1/authenticate');

        try {
            $response = $this->httpPost($url, [
                'Username' => strtolower($email),
                'Password' => $password,
            ]);

            if ($response->getStatusCode() == 200) {
                $authResponse = Response\AuthResponse::buildFromResponse($response);

                $this->setUserId($authResponse->getUserId());
                $this->setToken($authResponse->getToken());

                return $authResponse;
            }
        } catch (Exception\ResponseException $e) {
            switch ($e->getDetail()) {
                case 'account-locked/max-login-attempts':
                    return (new Response\AuthResponse)->setErrorDescription('locked');
                case 'account-not-active':
                    return (new Response\AuthResponse)->setErrorDescription('not-activated');
            }
        }

        return (new Response\AuthResponse)->setErrorDescription('authentication-failed');
    }

    /**
     * Registers an (unactivated) account with Auth and returns the activation token as a string.
     *
     * @param $email
     * @param $password
     * @return string|\Exception|Exception\ResponseException
     */
    public function registerAccount($email, $password)
    {
        $url = new Uri($this->authBaseUri . '/v1/users');

        try {
            $response = $this->httpPost($url, [
                'Username' => strtolower($email),
                'Password' => $password,
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (isset($body['activation_token'])) {
                    return $body['activation_token'];
                }
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Activates an account using an Activation Token.
     *
     * @param $activationToken
     * @return mixed
     */
    public function activateAccount($activationToken)
    {
        $url = new Uri($this->authBaseUri . '/v1/users/activate');

        try {
            $response = $this->httpPost($url, [
                'Token' => $activationToken,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Returns user account details for a passed authentication token.
     *
     * @param $token
     * @return array|Exception\ResponseException|Response\Error
     */
    public function getTokenInfo($token)
    {
        $url = new Uri($this->authBaseUri . '/v1/authenticate');

        try {
            $response = $this->httpPost($url, [
                'Token' => $token,
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body)) {
                    return $body;
                }
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Deletes all of a user's LPAs, and then deletes the user's account on Auth.
     *
     * @return bool|\Exception|Exception\ResponseException|Response\Error
     */
    public function deleteUserAndAllTheirLpas()
    {
        $success = $this->deleteAllLpas();

        if (!$success) {
            return new Response\Error('cannot-delete-lpas');
        }

        $path = sprintf('/v1/users/%s', $this->getUserId());

        $url = new Uri($this->authBaseUri . $path);

        try {
            $response = $this->httpDelete($url);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Returns a password reset token for a given email address.
     *
     * @param $email
     * @return string|\Exception|mixed|Exception\ResponseException|Response\Error
     */
    public function requestPasswordReset($email)
    {
        $url = new Uri($this->authBaseUri . '/v1/users/password-reset');

        try {
            $response = $this->httpPost($url, [
                'Username' => strtolower($email),
            ]);
            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body)) {
                    // If we have the token, return it.
                    if (isset($body['token'])) {
                        return $body['token'];
                    }

                    // If we have activation_token, then the account has not been activated.
                    if (isset($body['activation_token'])) {
                        return new Exception\ResponseException('account-not-activated', $response->getStatusCode(), $response);
                    }
                }
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Takes a password reset token to apply a new password to a user account.
     *
     * @param $token
     * @param $newPassword
     * @return bool|\Exception|Exception\ResponseException|Response\Error
     */
    public function updateAuthPasswordWithToken($token, $newPassword)
    {
        $url = new Uri($this->authBaseUri . '/v1/users/password-reset-update');

        try {
            $response = $this->httpPost($url, [
                'Token' => $token,
                'NewPassword' => $newPassword,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Returns a token to be used for updating the current user's email address.
     *
     * @param $newEmailAddress
     * @return string|\Exception|Exception\ResponseException|Response\Error
     */
    public function requestEmailUpdate($newEmailAddress)
    {
        $path = sprintf('/v1/users/%s/email/%s', $this->getUserId(), $newEmailAddress);

        $url = new Uri($this->authBaseUri . $path);

        try {
            $response = $this->httpGet($url);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body) && isset($body['token'])) {
                    return $body['token'];
                }
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Updates a user's email address based on the passed token.
     *
     * @param $emailUpdateToken
     * @return bool|\Exception|Exception\ResponseException|Response\Error
     */
    public function updateAuthEmail($emailUpdateToken)
    {
        $url = new Uri($this->authBaseUri . '/v1/users/confirm-new-email');

        try {
            $response = $this->httpPost($url, [
                'Token' => $emailUpdateToken,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Updates a user's password, checked against their existing password.
     *
     * The password should be validated in advance to:
     *  - Be >= 6 characters
     *  - Contain at least one numeric digit
     *  - Contain at least one alphabet character
     *
     * The auth service will also validate this, but not return detailed error messages.
     *
     * @param $currentPassword
     * @param $newPassword
     * @return \Exception|string|Exception\ResponseException|Response\Error
     */
    public function updateAuthPassword($currentPassword, $newPassword)
    {
        $path = sprintf('/v1/users/%s/password', $this->getUserId());

        $url = new Uri($this->authBaseUri . $path);

        try {
            $response = $this->httpPost($url, [
                'CurrentPassword' => $currentPassword,
                'NewPassword' => $newPassword,
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body) && isset($body['token'])) {
                    return $body['token'];
                }
            }
        } catch (Exception\ResponseException $e) {
            return $e;
        }

        return new Exception\ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public function searchUsers(string $email)
    {
        $url = new Uri($this->authBaseUri . '/v1/users/search');

        /** @var ResponseInterface $response */
        $response = $this->httpGet($url, ['email' => $email]);

        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody(), true);

            return $body;
        }

        return false;
    }

    /**
     * Return user stats from the auth server
     *
     * @return bool|mixed
     */
    public function getAuthStats()
    {
//TODO - Get this working properly
        $response = $this->httpClient->get($this->authBaseUri . '/v1/stats');

        $code = $response->getStatusCode();

        if ($code != 200) {
            $this->recordErrorResponseDetails($response);
            return false;
        }

        return $response->json();
    }
}
