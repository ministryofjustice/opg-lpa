<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

trait ClientV2AuthTrait
{
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
}
