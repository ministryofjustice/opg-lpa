<?php

namespace Application\Model\Service\User;

use Application\Model\Service\Mail\Message as MailMessage;
use Opg\Lpa\Api\Client\Exception\ResponseException;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Exception;

class Register implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function registerAccount($email, $password, callable $routeCallback)
    {
        $logger = $this->getServiceLocator()->get('Logger');
        $logger->info('Account registration attempt for ' . $email);

        $client = $this->getServiceLocator()->get('ApiClient');
        $activationToken = $client->registerAccount(strtolower($email), $password);

        // A successful response is a string...
        if (!is_string($activationToken)) {
            if ($activationToken instanceof ResponseException) {
                if ($activationToken->getDetail() == 'username-already-exists') {
                    return "address-already-registered";
                }

                return $activationToken->getDetail();
            }

            return "unknown-error";
        }

        // Send the email
        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo($email);

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-signup');

        $content = $this->getServiceLocator()
                        ->get('TwigEmailRenderer')
                        ->loadTemplate('registration.twig')
                        ->render([
                            'callback' => $routeCallback($activationToken),
                        ]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject('Activate your lasting power of attorney account');
        }

        $html = new MimePart($content);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        try {
            $logger->info('Sending account registration email to ' . $email);

            $this->getServiceLocator()->get('MailTransport')->send($message);
        } catch (Exception $e) {
            $logger->err('Failed to send account registration email to ' . $email);

            return "failed-sending-email";
        }

        return true;
    }

    /**
     * Activate an account. i.e. confirm the email address.
     *
     * @param $token
     * @return bool
     */
    public function activateAccount($token)
    {
        //  This returns:
        //  TRUE - If the user account exists. The account has been activated.
        //  ResponseException - If the user account does not exist, or was already activated.
        $client = $this->getServiceLocator()->get('ApiClient');
        $result = $client->activateAccount($token);

        $logger = $this->getServiceLocator()->get('Logger');

        if ($result === true) {
            $logger->info('Account activation attempt with token was successful');
        } else {
            $logger->info('Account activation attempt with token failed, or was already activated');
        }

        return ($result === true);
    }
}
