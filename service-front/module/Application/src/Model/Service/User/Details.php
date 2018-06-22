<?php

namespace Application\Model\Service\User;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Session\Container;
use Exception;
use RuntimeException;

class Details extends AbstractEmailService implements ApiClientAwareInterface
{
    use ApiClientTrait;
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
        $response = $this->apiClient->httpGet('/v2/user/' . $this->getUserId());

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

        $response = $this->apiClient->httpPut('/v2/user/' . $this->getUserId(), $userDetails->toArray());

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
        $identity = $this->getAuthenticationService()->getIdentity();

        $this->getLogger()->info('Requesting email update to new email: ' . $email, $identity->toArray());

        try {
            //  Manually update the token in the client
            $this->apiClient->updateToken($identity->token());

            $response = $this->apiClient->httpPost(sprintf('/v2/users/%s/email', $this->getUserId()), [
                'newEmail' => strtolower($email),
            ]);

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
        } catch (ResponseException $ex) {
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
            $response = $this->apiClient->httpPost(sprintf('/v2/users/email'), [
                'emailUpdateToken' => $emailUpdateToken,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (ResponseException $ignore) {}

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
            //  Manually update the token in the client
            $this->apiClient->updateToken($identity->token());

            $response = $this->apiClient->httpPost(sprintf('/v2/users/%s/password', $this->getUserId()), [
                'currentPassword' => $currentPassword,
                'newPassword'     => $newPassword,
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
     * @return bool|mixed
     */
    public function getTokenInfo($token)
    {
        $response = $this->apiClient->httpPost('/v2/authenticate', [
            'authToken' => $token,
        ]);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }

    /**
     * Deletes a user and their LPAs
     *
     * @return bool
     */
    public function delete()
    {
        $this->getLogger()->info('Deleting user and all their LPAs', $this->getAuthenticationService()->getIdentity()->toArray());

        //  The API to delete the user will delete their associated LPAs too
        $response = $this->apiClient->httpDelete('/v2/user/' . $this->getUserId());

        if ($response->getStatusCode() == 204) {
            return true;
        }

        return false;
    }

    public function requestPasswordResetEmail($email)
    {
        $logger = $this->getLogger();

        $logger->info('User requested password reset email');

        try {
            $resetToken = $this->requestPasswordReset(strtolower($email));

            //  A successful response is a string...
            if (!is_string($resetToken)) {
                return "unknown-error";
            }

            try {
                $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_PASSWORD_RESET, [
                    'token' => $resetToken,
                ]);
            } catch (Exception $e) {
                return "failed-sending-email";
            }

            $logger->info('Password reset email sent to ' . $email);

            return true;
        } catch (ResponseException $rex) {
            if ($rex->getMessage() == 'account-not-activated') {
                $body = json_decode($rex->getResponse()->getBody(), true);

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
            if ($rex->getCode() == 404) {
                try {
                    $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_PASSWORD_RESET_NO_ACCOUNT);
                } catch (Exception $e) {
                    return "failed-sending-email";
                }

                return true;
            }

            if ($rex->getDetail() != null) {
                return trim($rex->getDetail());
            }
        }

        return false;
    }

    /**
     * Returns a password reset token for a given email address
     *
     * @param $email
     * @return mixed
     */
    private function requestPasswordReset($email)
    {
        $response = $this->apiClient->httpPost('/v2/users/password-reset', [
            'username' => strtolower($email),
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
                    throw new ResponseException('account-not-activated', $response->getStatusCode(), $response);
                }
            }
        }

        throw new ResponseException('unknown-error', $response->getStatusCode(), $response);
    }

    public function setNewPassword($restToken, $password)
    {
        $this->getLogger()->info('Setting new password following password reset');

        $result = null;

        try {
            $response = $this->apiClient->httpPost('/v2/users/password', [
                'passwordToken' => $restToken,
                'newPassword'   => $password,
            ]);

            if ($response->getStatusCode() == 204) {
                return true;
            }
        } catch (ResponseException $rex) {
            if ($rex->getDetail() == 'Invalid token') {
                return 'invalid-token';
            } elseif ($rex->getDetail() != null) {
                return trim($rex->getDetail());
            }
        }

        return 'unknown-error';
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
            $response = $this->apiClient->httpPost('/v2/users', [
                'username' => strtolower($email),
                'password' => $password,
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
            }
        } catch (ResponseException $e) {
            $result = $e->getDetail();

            if ($result == 'username-already-exists') {
                $result = 'address-already-registered';
            }
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
        $accountActivationToken = null;

        //  Trigger a request to reset the password in the API - this will return the activation token or throw an exception
        try {
            $resetToken = $this->requestPasswordReset(strtolower($email));
        } catch (ResponseException $rex) {
            //  Only take any action if the not activated message was returned
            if ($rex->getMessage() == 'account-not-activated') {
                $body = json_decode($rex->getResponse()->getBody(), true);

                if (isset($body['activation_token'])) {
                    $accountActivationToken = $body['activation_token'];
                }
            }
        }

        if (!is_null($accountActivationToken)) {
            try {
                $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_ACCOUNT_ACTIVATE, [
                    'token' => $accountActivationToken,
                ]);
            } catch (Exception $e) {
                return "failed-sending-email";
            }

            return true;
        }

        //  If a proper reset token was returned, or the exception thrown was NOT account-not-activated then
        //  something has gone wrong so return false - when using this function the account should existing
        //  but be inactive so an exception of account-not-activated is the only "valid" outcome above
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
            $response = $this->apiClient->httpPost('/v2/users', [
                'activationToken' => $token,
            ]);

            if ($response->getStatusCode() == 204) {
                $logger->info('Account activation attempt with token was successful');

                return true;
            }
        } catch (ResponseException $ignore) {}

        $logger->info('Account activation attempt with token failed, or was already activated');

        return false;
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
