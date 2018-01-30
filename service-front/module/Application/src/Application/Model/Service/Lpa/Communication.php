<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\Mail\Message as MailMessage;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Exception;

/**
 * A model service class for sending emails on LPA creation and completion.
 *
 * Class Communication
 * @package Application\Model\Service\Lpa
 */
class Communication
{
    use LoggerTrait;

    public function sendRegistrationCompleteEmail(Lpa $lpa)
    {
        // Send the email
        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');

        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $userSession = $this->getServiceLocator()->get('UserDetailsSession');

        // Add the signed in user's email address.
        $message->addTo($userSession->user->email->address);

        // If we have a separate payment address, send the email to that also.
        if (!empty($lpa->payment->email) && ((string)$lpa->payment->email != strtolower($userSession->user->email->address))) {
            $message->addTo((string) $lpa->payment->email);
        }

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-complete-registration');

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('lpa-registration.twig')->render([
            'lpa' => $lpa,
            'paymentAmount' => ($lpa->payment->amount > 0 ? money_format('%i', $lpa->payment->amount) : null),
            'isHealthAndWelfare' => ($lpa->document->type === \Opg\Lpa\DataModel\Lpa\Document\Document::LPA_TYPE_HW),
        ]);

        //  Set the default subject
        $message->setSubject('Lasting power of attorney for ' . $lpa->document->donor->name . ' is ready to register');

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $subject = sprintf($matches[1], $lpa->document->donor->name);
            $message->setSubject($subject);
        }

        $html = new MimePart($content);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        try {
            $this->getServiceLocator()->get('MailTransport')->send($message);
        } catch (Exception $e) {
            $this->getLogger()->alert("Failed sending '".$subject."' email to ".$userSession->user->email->address." due to:\n".$e->getMessage());

            return "failed-sending-email";
        }

        return true;
    }
}
