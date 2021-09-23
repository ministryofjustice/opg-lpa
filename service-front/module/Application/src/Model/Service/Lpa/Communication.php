<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
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
        // Get the signed in user's email address.
        $userEmailAddress = $this->userDetailsSession->user->email->address;
        $to = [$userEmailAddress];

        $lpaTypeTitleCase = 'Health and welfare';
        if ($lpa->document->type === \Opg\Lpa\DataModel\Lpa\Document\Document::LPA_TYPE_PF) {
            $lpaTypeTitleCase = 'Property and financial affairs';
        }

        $donorName = '';
        if (isset($lpa->document->donor)) {
            $donorName = '' . $lpa->document->donor->name;
        }

        $data = [
            'donorName' => $donorName,
            'lpaType' => strtolower($lpaTypeTitleCase),
            'lpaId' => $this->formatLpaId($lpa->id),
            'viewDocsUrl' => $this->url('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]),
            'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->id], ['force_canonical' => true]),
        ];

        // The template we use depends on whether we have a payment or not
        $emailTemplateRef = AbstractEmailService::EMAIL_LPA_REGISTRATION;
        if (!is_null($lpa->payment)) {
            $emailTemplateRef = AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT;

            // Add extra data to the LPA registration email if a payment was made
            $amount = '';
            if (isset($lpa->payment->amount)) {
                $amount = $this->moneyFormat($lpa->payment->amount);
            }

            // Assume datetimes are in Europe/London timezone as all our users are in the UK
            $paymentDate = '';
            if (isset($lpa->payment->date)) {
                $lpa->payment->date->setTimezone(new DateTimeZone('Europe/London'));
                $paymentDate = $lpa->payment->date->format('j F Y - g:ia');
            }

            $data = array_merge($data, [
                'lpaTypeTitleCase' => $lpaTypeTitleCase,
                'lpaPaymentReference' => $lpa->payment->reference,
                'lpaPaymentDate' => $paymentDate,
                'paymentAmount' => $amount,
            ]);

            // If we have a separate payment address, send the email to that also
            if (!empty($lpa->payment->email) && ((string)$lpa->payment->email != strtolower($userEmailAddress))) {
                $to[] = (string) $lpa->payment->email;
            }
        }

        try {
            $mailParameters = new MailParameters($to, $emailTemplateRef, $data);
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
