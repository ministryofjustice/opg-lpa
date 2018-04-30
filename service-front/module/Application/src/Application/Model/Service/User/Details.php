<?php

namespace Application\Model\Service\User;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ResponseException as ApiResponseException;
use Application\Model\Service\AuthClient\AuthClientAwareInterface;
use Application\Model\Service\AuthClient\AuthClientTrait;
use Application\Model\Service\AuthClient\Exception\ResponseException as AuthResponseException;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Session\Container;
use Exception;
use RuntimeException;

class Details extends AbstractEmailService implements ApiClientAwareInterface, AuthClientAwareInterface
{
    use ApiClientTrait;
    use AuthClientTrait;
    use LoggerTrait;

    /**
     * @var Container
     */
    private $userDetailsSession;

    /**
     * @return bool|User
     */
    public function getUserDetails()
    {
        $response = $this->apiClient->httpGet('/v2/users/' . $this->getUserId());

        if ($response->getStatusCode() == 200) {
            return new User(json_decode($response->getBody(), true));
        }

        return false;
    }

    /**
     * Update the user's basic details
     *
     * @param array $data
     * @return mixed
     */
    public function updateAllDetails(array $data)
    {
        $identity = $this->getAuthenticationService()->getIdentity();

        $this->getLogger()->info('Updating user details', $identity->toArray());

        //  Load the existing details then add the updated data
        $userDetails = $this->getUserDetails();
        $userDetails->populateWithFlatArray($data);

        // Check if the user has removed their address
        if (array_key_exists('address', $data) && $data['address'] == null) {
            $userDetails->address = null;
        }

        // Check if the user has removed their DOB
        if (!isset($data['dob-date'])) {
            $userDetails->dob = null;
        }

        $validator = $userDetails->validate();

        if ($validator->hasErrors()) {
            throw new RuntimeException('Unable to save details');
        }

        $response = $this->apiClient->httpPut('/v2/users/' . $this->getUserId(), $userDetails->toArray());

        if ($response->getStatusCode() != 200) {
            throw new RuntimeException('Unable to save details');
        }

        return $userDetails;
    }

    /**
     * Update the user's email address.
     *
     * @param string $email
     * @param string $currentAddress
     * @return bool|string
     */
    public function requestEmailUpdate($email, $currentAddress)
    {
        $identityArray = $this->getAuthenticationService()->getIdentity()->toArray();

        $this->getLogger()->info('Requesting email update to new email: ' . $email, $identityArray);

        try {
            $response = $this->authClient->httpGet(sprintf('/v1/users/%s/email/%s', $this->getUserId(), strtolower($email)));

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body) && isset($body['token'])) {
                    //  Send the new email address received notification - ignore any failures
                    try {
                        $this->getMailTransport()->sendMessageFromTemplate($currentAddress, MailTransport::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY, [
                            'newEmailAddress' => $email,
                        ]);
                    } catch (Exception $ignore) {}

                    //  Send the new email address verify email
                    try {
                        $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_NEW_EMAIL_ADDRESS_VERIFY, [
                            'token' => $body['token'],
                        ]);
                    } catch (Exception $e) {
                        return "failed-sending-email";
                    }

                    return true;
                }
            }
        } catch (AuthResponseException $ex) {
            //  Get the real error out of the exception details
            switch ($ex->getDetail()) {
                case 'User already has this email':
                    return 'user-already-has-email';
                case 'Email already exists for another user':
                    return 'email-already-exists';
            }
        }

        return "unknown-error";
    }

    public function updateEmailUsingToken($emailUpdateToken)
    {
        $this->getLogger()->info('Updating email using token');

        try {
            $response = $this->authClient->httpPost('/v1/users/confirm-new-email', [
                'Token' => $emailUpdateToken,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (AuthResponseException $ignore) {}

        return false;
    }

    /**
     * Update the user's password
     *
     * @param $currentPassword
     * @param $newPassword
     * @return bool|string
     */
    public function updatePassword($currentPassword, $newPassword)
    {
        $identity = $this->getAuthenticationService()->getIdentity();

        $this->getLogger()->info('Updating password', $identity->toArray());

        try {
            $response = $this->authClient->httpPost(sprintf('/v1/users/%s/password', $this->getUserId()), [
                'CurrentPassword' => $currentPassword,
                'NewPassword' => $newPassword,
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body) && isset($body['token'])) {
                    $email = $this->userDetailsSession->user->email->address;

                    //  Send the password changed email - ignore any errors
                    try {
                        $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_PASSWORD_CHANGED, [
                            'email' => $email
                        ]);
                    } catch (Exception $ignore) {}

                    // Update the identity with the new token to avoid being
                    // logged out after the redirect. We don't need to update the token
                    // on the API client because this will happen on the next request
                    // when it reads it from the identity.
                    $identity->setToken($body['token']);

                    return true;
                }
            }
        } catch (Exception $ignore) {}

        return 'unknown-error';
    }

    /**
     * Returns user account details for a passed authentication token.
     *
     * @param $token
     * @return AuthResponseException|Exception|mixed
     */
    public function getTokenInfo($token)
    {
        try {
            $response = $this->authClient->httpPost('/v1/authenticate', [
                'Token' => $token,
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (is_array($body)) {
                    return $body;
                }
            }
        } catch (AuthResponseException $e) {
            return $e;
        }

        return new AuthResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    /**
     * Deletes a user and their LPAs
     *
     * @return ApiResponseException|AuthResponseException|bool|Exception
     */
    public function delete()
    {
        $this->getLogger()->info('Deleting user and all their LPAs', $this->getAuthenticationService()->getIdentity()->toArray());

        //  The API to delete the user will delete their associated LPAs too
        $response = $this->apiClient->httpDelete('/v2/users/' . $this->getUserId());

        if ($response->getStatusCode() != 204) {
            return false;
        }

        try {
            $response = $this->authClient->httpDelete('/v1/users/' . $this->getUserId());

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (AuthResponseException $e) {
            return $e;
        }

        return new ApiResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    public function requestPasswordResetEmail($email)
    {
        $logger = $this->getLogger();

        $logger->info('User requested password reset email');

        $resetToken = $this->requestPasswordReset(strtolower($email));

        //  A successful response is a string...
        if (!is_string($resetToken)) {
            if ($resetToken instanceof AuthResponseException) {
                if ($resetToken->getMessage() == 'account-not-activated') {
                    $body = json_decode($resetToken->getResponse()->getBody(), true);

                    if (isset($body['activation_token'])) {
                        //  If they have not yet activated their account we re-send them the activation link via the register service
                        try {
                            $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_ACCOUNT_ACTIVATE_PASSWORD_RESET, [
                                'token' => $body['activation_token'],
                            ]);
                        } catch (Exception $ex) {
                            $logger->err('Failed to send account activate email when triggering password reset: ' . $ex->getMessage());
                        }
                    }
                }

                // 404 response means user not found...
                if ($resetToken->getCode() == 404) {
                    try {
                        $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_PASSWORD_RESET_NO_ACCOUNT);
                    } catch (Exception $e) {
                        return "failed-sending-email";
                    }

                    return true;
                }

                if ($resetToken->getDetail() != null) {
                    return trim($resetToken->getDetail());
                }
            }

            return "unknown-error";
        }

        // Send the password reset email
        try {
            $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_PASSWORD_RESET, [
                'token' => $resetToken,
            ]);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        $logger->info('Password reset email sent to ' . $email);

        return true;
    }

    /**
     * Returns a password reset token for a given email address
     *
     * @param $email
     * @return AuthResponseException|Exception|mixed
     */
    private function requestPasswordReset($email)
    {
        try {
            $response = $this->authClient->httpPost('/v1/users/password-reset', [
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
                        return new AuthResponseException('account-not-activated', $response->getStatusCode(), $response);
                    }
                }
            }
        } catch (AuthResponseException $e) {
            return $e;
        }

        return new AuthResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    public function setNewPassword($restToken, $password)
    {
        $this->getLogger()->info('Setting new password following password reset');

        $result = null;

        try {
            $response = $this->authClient->httpPost('/v1/users/password-reset-update', [
                'Token' => $restToken,
                'NewPassword' => $password,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }

            $result = new AuthResponseException('unknown-error', $response->getStatusCode(), $response);
        } catch (AuthResponseException $e) {
            $result = $e;
        }

        if ($result->getDetail() == 'Invalid token') {
            return "invalid-token";
        } elseif ($result->getDetail() != null) {
            return trim($result->getDetail());
        }

        return "unknown-error";
    }

    /**
     * Register the user account and send the activate account email
     *
     * @param $email
     * @param $password
     * @return bool|mixed|string
     */
    public function registerAccount($email, $password)
    {
        $this->getLogger()->info('Account registration attempt for ' . $email);

        $result = 'unknown-error';

        try {
            $response = $this->authClient->httpPost('/v1/users', [
                'Username' => strtolower($email),
                'Password' => $password,
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody(), true);

                if (isset($body['activation_token'])) {
                    try {
                        $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_ACCOUNT_ACTIVATE, [
                            'token' => $body['activation_token'],
                        ]);
                    } catch (Exception $e) {
                        return "failed-sending-email";
                    }

                    return true;
                }
            } elseif ($response instanceof AuthResponseException) {
                if ($response->getDetail() == 'username-already-exists') {
                    $result = 'address-already-registered';
                } else {
                    $result = $response->getDetail();
                }
            }
        } catch (AuthResponseException $e) {
            $result = $e->getDetail();
        }

        return $result;
    }

    /**
     * Resend the activate email to an inactive user
     *
     * @param $email
     * @return bool|string
     */
    public function resendActivateEmail($email)
    {
        //  Trigger a request to reset the password in the API - this will return the activation token
        $resetToken = $this->requestPasswordReset(strtolower($email));

        if ($resetToken instanceof AuthResponseException && $resetToken->getMessage() == 'account-not-activated') {
            $body = json_decode($resetToken->getResponse()->getBody(), true);

            if (isset($body['activation_token'])) {
                // If they have not yet activated their account, we re-send them the activation link.
                try {
                    $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_ACCOUNT_ACTIVATE, [
                        'token' => $body['activation_token'],
                    ]);
                } catch (Exception $e) {
                    return "failed-sending-email";
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Activate an account. i.e. confirm the email address.
     *
     * @param $token
     * @return bool
     */
    public function activateAccount($token)
    {
        $logger = $this->getLogger();

        try {
            $response = $this->authClient->httpPost('/v1/users/activate', [
                'Token' => $token,
            ]);

            if ($response->getStatusCode() == 204) {
                $logger->info('Account activation attempt with token was successful');

                return true;
            }
        } catch (AuthResponseException $ignore) {}

        $logger->info('Account activation attempt with token failed, or was already activated');

        return false;
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
