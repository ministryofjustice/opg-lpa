<?php

namespace Application\Model\Service\User;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Mail\MailParameters;
use MakeShared\DataModel\User\User;
use Laminas\Mail\Exception\ExceptionInterface;
use Laminas\Session\Container;
use Exception;
use RuntimeException;
use MakeShared\Telemetry\TelemetryEventManager;

/**
 * On many of the methods in this class where we're catching
 * Laminas\Mail\Exception\ExceptionInterface,
 * psalm (linting) complains about catching that interface; however, this is
 * because ExceptionInterface does not implement Throwable
 * or extend Exception (i.e. issue is nothing to do with our code).
 * We use `psalm-suppress InvalidCatch` to deal with these.
 */
class Details extends AbstractEmailService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * @var Container
     */
    private $userDetailsSession;

    /**
     * @return bool|User
     */
    public function getUserDetails()
    {
        try {
            return new User($this->apiClient->httpGet('/v2/user/' . $this->getUserId()));
        } catch (ApiException $ex) {
            $this->getLogger()->err($ex->getMessage());
        }

        return false;
    }

    /**
     * Update the user's basic details
     *
     * @param array $data
     * @return array|null|string
     * @throws RuntimeException
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

        return $this->apiClient->httpPut('/v2/user/' . $this->getUserId(), $userDetails->toArray());
    }

    /**
     * Update the user's email address.
     *
     * @param string $email The new email address
     * @param string $currentAddress The current email address
     * @return bool|string
     *
     * @psalm-suppress InvalidCatch
     */
    public function requestEmailUpdate($email, $currentAddress)
    {
        $identity = $this->getAuthenticationService()->getIdentity();

        $logger = $this->getLogger();

        $logger->info('Requesting email update to new email: ' . $email, $identity->toArray());

        try {
            //  Manually update the token in the client
            $this->apiClient->updateToken($identity->token());

            $result = $this->apiClient->httpPost(sprintf('/v2/users/%s/email', $this->getUserId()), [
                'newEmail' => strtolower($email),
            ]);

            if (is_array($result) && isset($result['token'])) {
                // Send notification to old email address that a new email address
                // has been set
                $mailParameters = new MailParameters(
                    $currentAddress,
                    AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY,
                    ['newEmailAddress' => $email]
                );

                try {
                    $this->getMailTransport()->send($mailParameters);
                } catch (ExceptionInterface $ex1) {
                    $logger->err($ex1->getMessage());
                }

                // Send the new email address an email with link to verify that
                // the new email address is correct
                $changeEmailAddressUrl = $this->url(
                    'user/change-email-address/verify',
                    ['token' => $result['token']],
                    ['force_canonical' => true]
                );

                $mailParameters = new MailParameters(
                    $email,
                    AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_VERIFY,
                    ['changeEmailAddressUrl' => $changeEmailAddressUrl]
                );

                try {
                    $this->getMailTransport()->send($mailParameters);
                } catch (ExceptionInterface $ex2) {
                    $logger->err($ex2->getMessage());
                    return 'failed-sending-email';
                }

                return true;
            }
        } catch (ApiException $ex3) {
            $logger->err($ex3->getMessage());

            //  Get the real error out of the exception details
            switch ($ex3->getMessage()) {
                case 'User already has this email':
                    return 'user-already-has-email';
                case 'Email already exists for another user':
                    return 'email-already-exists';
            }
        }

        return 'unknown-error';
    }

    public function updateEmailUsingToken($emailUpdateToken)
    {
        $logger = $this->getLogger();

        $logger->info('Updating email using token');

        try {
            $this->apiClient->httpPost(sprintf('/v2/users/email'), [
                'emailUpdateToken' => $emailUpdateToken,
            ]);

            return true;
        } catch (ApiException $ex) {
            $logger->err($ex->getMessage());
        }

        return false;
    }

    /**
     * Update the user's password
     *
     * @param $currentPassword
     * @param $newPassword
     * @return bool|string
     *
     * @psalm-suppress InvalidCatch
     */
    public function updatePassword($currentPassword, $newPassword)
    {
        $logger = $this->getLogger();

        $identity = $this->getAuthenticationService()->getIdentity();

        $logger->info('Updating password', $identity->toArray());

        try {
            //  Manually update the token in the client
            $this->apiClient->updateToken($identity->token());

            $result = $this->apiClient->httpPost(sprintf('/v2/users/%s/password', $this->getUserId()), [
                'currentPassword' => $currentPassword,
                'newPassword'     => $newPassword,
            ]);

            if (is_array($result) && isset($result['token'])) {
                $email = $this->userDetailsSession->user->email->address;

                $mailParameters = new MailParameters(
                    $email,
                    AbstractEmailService::EMAIL_PASSWORD_CHANGED,
                    ['email' => $email]
                );

                //  Send the password changed email - ignore any errors
                try {
                    $this->getMailTransport()->send($mailParameters);
                } catch (ExceptionInterface $ex) {
                    $logger->err($ex->getMessage());
                }

                // Update the identity with the new token to avoid being
                // logged out after the redirect. We don't need to update the token
                // on the API client because this will happen on the next request
                // when it reads it from the identity.
                $identity->setToken($result['token']);

                return true;
            }
        } catch (ApiException $ex) {
            $logger->err($ex->getMessage());
        }

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
        try {
            $response = $this->apiClient->httpPost('/v2/authenticate', [
                'authToken' => $token,
            ]);

            $success = true;
            if (isset($response['expiresIn'])) {
                $expiresIn = $response['expiresIn'];
            }
            $failureCode = null;
        } catch (ApiException $ex) {
            $this->getLogger()->err($ex->getMessage());

            $success = false;
            $expiresIn = null;
            $failureCode = $ex->getStatusCode();
        }

        return [
            'success' => $success,
            'failureCode' => $failureCode,
            'expiresIn' => $expiresIn,
        ];
    }

    /**
     * Deletes a user and their LPAs
     *
     * @return bool
     */
    public function delete()
    {
        $logger = $this->getLogger();

        $logger->info(
            'Deleting user and all their LPAs',
            $this->getAuthenticationService()->getIdentity()->toArray()
        );

        try {
            $this->apiClient->httpDelete('/v2/user/' . $this->getUserId());
        } catch (ApiException $ex) {
            $logger->err($ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param $email
     * @return bool|string
     *
     * @psalm-suppress InvalidCatch
     */
    public function requestPasswordResetEmail($email)
    {
        $logger = $this->getLogger();

        $logger->info('User requested password reset email');

        try {
            $result = $this->apiClient->httpPost('/v2/users/password-reset', [
                'username' => strtolower($email),
            ]);

            //  If there is an activation token then the account isn't active yet
            if (is_array($result)) {
                if (isset($result['activation_token'])) {
                    return $this->sendAccountActivateEmail($email, $result['activation_token']);
                }

                if (isset($result['token'])) {
                    $forgotPasswordUrl = $this->url(
                        'forgot-password/callback',
                        ['token' => $result['token']],
                        ['force_canonical' => true]
                    );

                    $mailParameters = new MailParameters(
                        $email,
                        AbstractEmailService::EMAIL_PASSWORD_RESET,
                        ['forgotPasswordUrl' => $forgotPasswordUrl]
                    );

                    try {
                        $this->getMailTransport()->send($mailParameters);
                    } catch (ExceptionInterface $ex) {
                        $logger->err($ex->getMessage());
                        return "failed-sending-email";
                    }

                    $logger->info('Password reset email sent to ' . $email);

                    return true;
                }
            }

            return 'unknown-error';
        } catch (ApiException $ex) {
            // 404 response means user not found...
            if ($ex->getCode() == 404) {
                $signUpUrl = $this->url('register', [], ['force_canonical' => true]);

                $mailParameters = new MailParameters(
                    $email,
                    AbstractEmailService::EMAIL_PASSWORD_RESET_NO_ACCOUNT,
                    ['signUpUrl' => $signUpUrl]
                );

                try {
                    $this->getMailTransport()->send($mailParameters);
                } catch (ExceptionInterface $ex) {
                    $logger->err($ex->getMessage());
                    return "failed-sending-email";
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param $email
     * @param $activationToken
     * @return bool|string
     *
     * @psalm-suppress InvalidCatch
     */
    private function sendAccountActivateEmail($email, $activationToken)
    {
        $activateAccountUrl = $this->url(
            'register/confirm',
            ['token' => $activationToken],
            ['force_canonical' => true]
        );

        $mailParameters = new MailParameters(
            $email,
            AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE,
            ['activateAccountUrl' => $activateAccountUrl]
        );

        try {
            $this->getMailTransport()->send($mailParameters);
        } catch (ExceptionInterface $ex) {
            $this->getLogger()->err($ex->getMessage());
            return 'failed-sending-email';
        }

        return true;
    }

    /**
     * @param $restToken
     * @param $password
     * @return bool|string
     */
    public function setNewPassword($restToken, $password)
    {
        $logger = $this->getLogger();

        $logger->info('Setting new password following password reset');

        try {
            $result = $this->apiClient->httpPost('/v2/users/password', [
                'passwordToken' => $restToken,
                'newPassword'   => $password,
            ]);

            //  Result should be null to confirm 204 response
            if (is_null($result)) {
                return true;
            }
        } catch (ApiException $ex) {
            $msg = $ex->getMessage();
            $logger->err($msg);

            if ($msg === 'Invalid passwordToken') {
                return 'invalid-token';
            } elseif ($msg != null) {
                return trim($msg);
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
     *
     * @psalm-suppress InvalidCatch
     */
    public function registerAccount($email, $password)
    {
        $logger = $this->getLogger();

        $logger->info('Account registration attempt for ' . $email);

        try {
            $result = $this->apiClient->httpPost('/v2/users', [
                'username' => strtolower($email),
                'password' => $password,
            ]);

            if (isset($result['activation_token'])) {
                $activateAccountUrl = $this->url(
                    'register/confirm',
                    ['token' => $result['activation_token']],
                    ['force_canonical' => true]
                );

                $mailParameters = new MailParameters(
                    $email,
                    AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE,
                    ['activateAccountUrl' => $activateAccountUrl]
                );

                try {
                    $this->getMailTransport()->send($mailParameters);
                } catch (ExceptionInterface $ex1) {
                    $logger->err($ex1->getMessage());
                    return 'failed-sending-email';
                }

                return true;
            }
        } catch (ApiException $ex2) {
            if ($ex2->getMessage() == 'username-already-exists') {
                $mailParameters = new MailParameters(
                    $email,
                    AbstractEmailService::EMAIL_ACCOUNT_DUPLICATION_WARNING
                );

                try {
                    $this->getMailTransport()->send($mailParameters);
                } catch (ExceptionInterface $ex3) {
                    $logger->err($ex3->getMessage());
                    return 'failed-sending-warning-email';
                }
                return 'address-already-registered';
            }

            return 'api-error';
        }

        return 'unknown-error';
    }

    /**
     * Resend the activate email to an inactive user
     *
     * @param $email
     * @return bool|string
     */
    public function resendActivateEmail($email)
    {
        // Trigger a request to reset the password in the API - this will return the activation token or
        // throw an exception
        try {
            $result = $this->apiClient->httpPost('/v2/users/password-reset', [
                'username' => strtolower($email),
            ]);

            if (isset($result['activation_token'])) {
                return $this->sendAccountActivateEmail($email, $result['activation_token']);
            }
        } catch (ApiException $ex) {
            $this->getLogger()->err($ex->getMessage());
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
            $result = $this->apiClient->httpPost('/v2/users', [
                'activationToken' => $token,
            ]);

            $logger->info('Account activation attempt with token was successful');

            return true;
        } catch (ApiException $ex) {
            $logger->err($ex->getMessage());
        }

        $logger->info('Account activation attempt with token failed, or was already activated');

        return false;
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
