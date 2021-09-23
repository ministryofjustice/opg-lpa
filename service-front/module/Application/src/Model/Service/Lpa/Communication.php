<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\Mail\Transport\MailTransport;
use Opg\Lpa\DataModel\Lpa\Lpa;
use DateTime;
use DateTimeZone;
use Exception;
use Laminas\Mail\Exception\ExceptionInterface;
use Laminas\Session\Container;

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
        $to = [$userEmailAddress];

        // If we have a separate payment address, send the email to that also.
        if (!empty($lpa->payment->email) && ((string)$lpa->payment->email != strtolower($userEmailAddress))) {
            $to[] = (string) $lpa->payment->email;
        }

        $amount = $lpa->payment->amount;
        if (!is_numeric($amount)) {
            $amount = null;
        }

        $lpaTypeTitleCase = 'Health and welfare';
        if ($lpa->document->type === \Opg\Lpa\DataModel\Lpa\Document\Document::LPA_TYPE_PF) {
            $lpaTypeTitleCase = 'Property and financial affairs';
        }

        // Assume datetimes are in Europe/London timezone as all our users are in the UK
        $lpa->payment->date->setTimezone(new DateTimeZone('Europe/London'));

        $data = [
            'donorName' => $lpa->document->donor->name,
            'lpaType' => strtolower($lpaTypeTitleCase),
            'lpaTypeTitleCase' => $lpaTypeTitleCase,
            'lpaId' => $this->formatLpaId($lpa->id),
            'lpaHasPaymentReference' => (!is_null($lpa->payment->reference)),
            'lpaPaymentReference' => $lpa->payment->reference,
            'lpaPaymentDate' => $lpa->payment->date->format('j F Y - g:ia'),
            'paymentAmount' => $this->moneyFormat($amount),
            'viewDocsUrl' => $this->url('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]),
            'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->id], ['force_canonical' => true]),
        ];

        try {
            $mailParameters = new MailParameters($to, AbstractEmailService::EMAIL_LPA_REGISTRATION, $data);
            $this->getMailTransport()->send($mailParameters);
        } catch (ExceptionInterface $ex) {
            $this->getLogger()->err($ex);
            return "failed-sending-email";
        }

        return true;
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
