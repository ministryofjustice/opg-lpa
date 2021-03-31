<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Exception;
use Laminas\Session\Container;
use function number_format;
use function floatval;

/**
 * A model service class for sending emails on LPA creation and completion.
 *
 * Class Communication
 * @package Application\Model\Service\Lpa
 */
class Communication extends AbstractEmailService
{
    /**
     * @var Container
     */
    private $userDetailsSession;

    public function sendRegistrationCompleteEmail(Lpa $lpa)
    {
        //  Get the user email address
        $userEmailAddress = $this->userDetailsSession->user->email->address;

        // Add the signed in user's email address.
        $to = [
            $userEmailAddress,
        ];

        // If we have a separate payment address, send the email to that also.
        if (!empty($lpa->payment->email) && ((string)$lpa->payment->email != strtolower($userEmailAddress))) {
            $to[] = (string) $lpa->payment->email;
        }

        $data = [
            'lpa' => $lpa,
            'paymentAmount' => (is_numeric($lpa->payment->amount) ? number_format(floatval($lpa->payment->amount), 2) : null),
            'isHealthAndWelfare' => ($lpa->document->type === \Opg\Lpa\DataModel\Lpa\Document\Document::LPA_TYPE_HW),
        ];

        try {
            $this->getMailTransport()->sendMessageFromTemplate($to, MailTransport::EMAIL_LPA_REGISTRATION, $data);
        } catch (Exception $e) {
            print($e);
            return "failed-sending-email";
        }

        return true;
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
