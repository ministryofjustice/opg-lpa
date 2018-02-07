<?php

namespace Application\Model\Service\User;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\Logger\LoggerTrait;
use Exception;

class PasswordReset extends AbstractEmailService
{
    use LoggerTrait;

    /**
     * @var Register
     */
    private $registerService;

    public function requestPasswordResetEmail($email)
    {
        $logger = $this->getLogger();

        $logger->info('User requested password reset email');

        $client = $this->getApiClient();
        $resetToken = $client->requestPasswordReset(strtolower($email));

        //  A successful response is a string...
        if (!is_string($resetToken)) {
            if ($resetToken instanceof ResponseException) {
                if ($resetToken->getMessage() == 'account-not-activated') {
                    $body = json_decode($resetToken->getResponse()->getBody(), true);

                    if (isset($body['activation_token'])) {
                        //  If they have not yet activated their account we re-send them the activation link via the register service
                        $result = $this->registerService->sendActivateEmail($email, $body['activation_token'], true);

                        return 'account-not-activated';
                    }
                }

                // 404 response means user not found...
                if ($resetToken->getCode() == 404) {
                    return "user-not-found";
                }

                if ($resetToken->getDetail() != null) {
                    return trim($resetToken->getDetail());
                }
            }

            return "unknown-error";
        }

        // Send the email
        $this->sendResetEmail($email, $resetToken);

        $logger->info('Password reset email sent to ' . $email);

        return true;
    }

    /**
     * Check if a given reset token is currently valid.
     *
     * @param $restToken
     * @return bool
     */
    public function isResetTokenValid($restToken)
    {
        // If we can exchange it for a string auth token, then it's valid.
        $authToken = $this->getApiClient()
                          ->requestPasswordResetAuthToken($restToken);

        return is_string($authToken);
    }

    public function setNewPassword($restToken, $password)
    {
        $this->getLogger()->info('Setting new password following password reset');

        $client = $this->getApiClient();
        $result = $client->updateAuthPasswordWithToken($restToken, $password);

        if ($result !== true) {
            if ($result instanceof ResponseException) {
                if ($result->getDetail() == 'Invalid token') {
                    return "invalid-token";
                }

                if ($result->getDetail() != null) {
                    return trim($result->getDetail());
                }
            }

            return "unknown-error";
        }

        return true;
    }

    private function sendResetEmail($email, $token)
    {
        $data = [
            'token' => $token,
        ];

        try {
            $this->getMailTransport()->sendMessageFromTemplate($email, MailTransport::EMAIL_PASSWORD_RESET, $data);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        return true;
    }

    public function setRegisterService(Register $registerService)
    {
        $this->registerService = $registerService;
    }
}
