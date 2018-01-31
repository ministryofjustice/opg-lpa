<?php

namespace Application\Model\Service\User;

use Application\Form\AbstractCsrfForm;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\Message as MailMessage;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Exception;
use RuntimeException;
use Zend\Session\Container;

class Details extends AbstractEmailService
{
    use LoggerTrait;

    /**
     * @var Container
     */
    private $userDetailsSession;

    public function load()
    {
        $client = $this->getApiClient();

        return $client->getAboutMe();
    }

    /**
     * Update the user's basic details.
     *
     * @param AbstractCsrfForm $details
     * @return mixed
     */
    public function updateAllDetails(AbstractCsrfForm $details)
    {
        $authenticationData = $this->getAuthenticationService()->getIdentity()->toArray();
        $this->getLogger()->info('Updating user details', $authenticationData);

        // Load the existing details...
        $client = $this->getApiClient();
        $userDetails = $client->getAboutMe();

        // Apply the new ones...
        $data = $details->getData();
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

        $result = $client->setAboutMe($userDetails);

        if ($result !== true) {
            throw new RuntimeException('Unable to save details');
        }

        return $userDetails;
    }

    /**
     * Update the user's email address.
     *
     * @param AbstractCsrfForm $details
     * @param string $currentAddress
     * @return bool|string
     */
    public function requestEmailUpdate(AbstractCsrfForm $details, $currentAddress)
    {
        $identityArray = $this->getAuthenticationService()->getIdentity()->toArray();

        $data = $details->getData();

        $this->getLogger()->info('Requesting email update to new email: ' . $data['email'], $identityArray);

        $client = $this->getApiClient();
        $updateToken = $client->requestEmailUpdate(strtolower($data['email']));

        if (!is_string($updateToken)) {
            if ($updateToken instanceof ResponseException) {
                switch ($updateToken->getDetail()) {
                    case 'User already has this email':
                        return 'user-already-has-email';
                    case 'Email already exists for another user':
                        return 'email-already-exists';
                }
            }

            return "unknown-error";
        }

        $this->sendNotifyNewEmailEmail($currentAddress, $data['email']);

        return $this->sendActivateNewEmailEmail($data['email'], $updateToken);
    }

    private function sendActivateNewEmailEmail($newEmailAddress, $token)
    {
        $this->getLogger()->info('Sending new email verification email');

        $message = new MailMessage();

        $config = $this->getConfig();
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo($newEmailAddress);

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-newemail-verification');

        $content = $this->getTwigEmailRenderer()->loadTemplate('new-email-verify.twig')
                        ->render([
                            'token' => $token,
                        ]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject('Please verify your new email address');
        }

        $html = new MimePart($content);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        try {
            $this->getMailTransport()->send($message);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        return true;
    }

    private function sendNotifyNewEmailEmail($oldEmailAddress, $newEmailAddress)
    {
        $this->getLogger()->info('Sending new email confirmation email');

        $message = new MailMessage();

        $config = $this->getConfig();
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo($oldEmailAddress);

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-newemail-confirmation');

        $content = $this->getTwigEmailRenderer()->loadTemplate('new-email-notify.twig')
                        ->render([
                            'newEmailAddress' => $newEmailAddress,
                        ]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject('You asked us to change your email address');
        }

        $html = new MimePart($content);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        try {
            $this->getMailTransport()->send($message);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        return true;
    }

    public function updateEmailUsingToken($emailUpdateToken)
    {
        $this->getLogger()->info('Updating email using token');

        $client = $this->getApiClient();

        $success = $client->updateAuthEmail($emailUpdateToken);

        return ($success === true);
    }

    /**
     * Update the user's password.
     *
     * @param AbstractCsrfForm $details
     * @return bool|string
     */
    public function updatePassword(AbstractCsrfForm $details)
    {
        $identity = $this->getAuthenticationService()->getIdentity();

        $this->getLogger()->info('Updating password', $identity->toArray());

        $client = $this->getApiClient();

        $data = $details->getData();

        $result = $client->updateAuthPassword(
            $data['password_current'],
            $data['password']
        );

        if (!is_string($result)) {
            return 'unknown-error';
        }

        $userSession = $this->userDetailsSession;
        $email = $userSession->user->email->address;
        $this->sendPasswordUpdatedEmail($email);

        // Update the identity with the new token to avoid being
        // logged out after the redirect. We don't need to update the token
        // on the API client because this will happen on the next request
        // when it reads it from the identity.
        $identity->setToken($result);

        return true;
    }

    public function sendPasswordUpdatedEmail($email)
    {
        $message = new MailMessage();

        $config = $this->getConfig();
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo($email);

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-password');
        $message->addCategory('opg-lpa-password-changed');

        $content = $this->getTwigEmailRenderer()->loadTemplate('password-changed.twig')
                        ->render([
                            'email' => $email
                        ]);

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $message->setSubject($matches[1]);
        } else {
            $message->setSubject('You have changed your LPA account password');
        }

        $html = new MimePart($content);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        try {
            $this->getMailTransport()->send($message);
        } catch (Exception $e) {
            return "failed-sending-email";
        }

        return true;
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
