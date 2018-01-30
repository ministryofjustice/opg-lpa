<?php

namespace Application\Model\Service\User;

use Application\Model\Service\Mail\Message as MailMessage;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Exception;

class PasswordReset
{
    use LoggerTrait;

    public function requestPasswordResetEmail($email)
    {
        $logger = $this->getLogger();
        $logger->info('User requested password reset email');

        $client = $this->getServiceLocator()->get('ApiClient');
        $resetToken = $client->requestPasswordReset(strtolower($email));

        //  A successful response is a string...
        if (!is_string($resetToken)) {
            if ($resetToken instanceof ResponseException) {
                if ($resetToken->getMessage() == 'account-not-activated') {
                    $body = json_decode($resetToken->getResponse()->getBody(), true);

                    if (isset($body['activation_token'])) {
                        //  If they have not yet activated their account we re-send them the activation link via the register service
                        $result = $this->getServiceLocator()->get('Register')->sendActivateEmail($email, $body['activation_token'], true);

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
        $authToken = $this->getServiceLocator()
                          ->get('ApiClient')
                          ->requestPasswordResetAuthToken($restToken);

        return is_string($authToken);
    }

    public function setNewPassword($restToken, $password)
    {
        $logger = $this->getLogger();
        $logger->info('Setting new password following password reset');

        $client = $this->getServiceLocator()->get('ApiClient');
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
        $logger = $this->getLogger();
        $logger->info('Sending password reset email');

        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo($email);

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-passwordreset');
        $message->addCategory('opg-lpa-passwordreset-normal');

        $content = $this->getServiceLocator()
                        ->get('TwigEmailRenderer')
                        ->loadTemplate('password-reset.twig')->render([
                            'token' => $token,
                        ]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject('Password reset request');
        }

        $html = new MimePart($content);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        try {
            $this->getServiceLocator()
                 ->get('MailTransport')
                 ->send($message);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        return true;
    }
}
