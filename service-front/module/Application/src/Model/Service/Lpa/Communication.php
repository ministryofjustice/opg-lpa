<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use MakeShared\DataModel\Lpa\Lpa;
use DateTimeZone;
use DateInterval;
use Exception;
use Laminas\Session\Container;
use MakeShared\Logging\LoggerTrait;

/**
 * A model service class for sending emails on LPA creation and completion.
 *
 * Class Communication
 * @package Application\Model\Service\Lpa
 */
class Communication extends AbstractEmailService
{
    use LoggerTrait;

    /** @var Container */
    private $userDetailsSession;
    private $emailTemplateRef;
    private $data;
    private $lpaTypeTitleCase;

    /**
     * Allow catch of ExceptionInterface exceptions
     * @psalm-suppress InvalidCatch
     */
    public function sendRegistrationCompleteEmail(Lpa $lpa)
    {
        // Get the signed in user's email address.
        $userEmailAddress = $this->userDetailsSession->user->email->address;
        $to = [$userEmailAddress];

        $this->lpaTypeTitleCase = 'Health and welfare';
        if ($lpa->document->type === \MakeShared\DataModel\Lpa\Document\Document::LPA_TYPE_PF) {
            $this->lpaTypeTitleCase = 'Property and financial affairs';
        }

        $donorName = '';
        if (isset($lpa->document->donor)) {
            $donorName = '' . $lpa->document->donor->name;
        }

        $this->data = [
            'donorName' => $donorName,
            'lpaType' => strtolower($this->lpaTypeTitleCase),
            'lpaId' => $this->formatLpaId($lpa->id),
            'viewDocsUrl' => $this->url('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]),
            'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->id], ['force_canonical' => true]),
        ];

        // We use 3 templates, for Cheque payment, Online payment or No payment
        // note that $lpa->payment is not null when we create an LPA through the site,
        // even if there was no fee paid (there's sometimes a payment with amount 0 e:g if person receives universal credit)

        if (!is_null($lpa->payment->reference)) {
            // we have a payment reference, so this is an online payment
            $to = $this->setUpEmailFieldsForOnlinePayment($lpa, $userEmailAddress, $to);
            $this->setUpEmailFieldsForPayments($lpa);
        } else {
            if ($lpa->payment->method == 'cheque') {
            // we have a cheque payment
                $this->setUpEmailFieldsForChequePayment($lpa);
                $this->setUpEmailFieldsForPayments($lpa);
            } else {
                // we have a zero payment, which is effectively no payment at this time (OPG may later contact the customer for payment)
                $this->setUpEmailFieldsForNoPayment($lpa);
            }
        }

        try {
            $mailParameters = new MailParameters($to, $this->emailTemplateRef, $this->data);
            $this->getMailTransport()->send($mailParameters);
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to send registration complete email', [
                'exception' => $ex,
            ]);

            return "failed-sending-email";
        }

        return true;
    }

    public function setUpEmailFieldsForOnlinePayment(Lpa $lpa, string $userEmailAddress, array $to)
    {
        $this->emailTemplateRef = AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1;

        $amount = '';
        if (isset($lpa->payment->amount)) {
            $amount = $this->moneyFormat($lpa->payment->amount);
        }

        // Assume datetimes are in Europe/London timezone as all our users are in the UK
        $paymentDate = '';
        $refundDate = '';
        if (isset($lpa->payment->date)) {
            $lpa->payment->date->setTimezone(new DateTimeZone('Europe/London'));
            $paymentDate = $lpa->payment->date->format('j F Y - g:ia');
            $refundDate = $lpa->payment->date->add(new DateInterval('P42D'))->format('j F Y');
        }

        $this->data = array_merge($this->data, [
            'lpaTypeTitleCase' => $this->lpaTypeTitleCase,
            'lpaPaymentReference' => $lpa->payment->reference,
            'lpaPaymentDate' => $paymentDate,
            'paymentAmount' => $amount,
            'date' => $refundDate,
        ]);

        // If we have a separate payment address, send the email to that also
        if (!empty($lpa->payment->email) && ((string)$lpa->payment->email != strtolower($userEmailAddress))) {
            $to = array_merge($to, [
                (string) $lpa->payment->email
            ]);
        }

        return $to;
    }

    public function setUpEmailFieldsForChequePayment(Lpa $lpa)
    {
        $this->emailTemplateRef = AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2;

        $amount = '';
        if (isset($lpa->payment->amount)) {
            $amount = $this->moneyFormat($lpa->payment->amount);
        }

        $this->data = array_merge($this->data, [
            'feeAmount' => $amount,
        ]);
    }

    public function setUpEmailFieldsForNoPayment(Lpa $lpa)
    {
        $this->emailTemplateRef = AbstractEmailService::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3;

        if (empty($lpa->document->peopleToNotify)) {
                $this->data = array_merge($this->data, [
                    'PTN' => false,
                ]);
        } else {
                $this->data = array_merge($this->data, [
                    'PTN' => true,
                ]);
        }
    }

    public function setUpEmailFieldsForPayments(Lpa $lpa)
    {
        // fill out email fields appropriately that apply to cheque and online payments
        //
        if (empty($lpa->document->peopleToNotify)) {
            if (is_null($lpa->payment->reducedFeeLowIncome)) {
                // we have no reduced fee, and no Person(s) to Notify
                $this->data = array_merge($this->data, [
                    'PTNOnly' => false,
                    'FeeFormOnly' => false,
                    'FeeFormPTN' => false,
                    'remission' => false,
                ]);
            } else {
                // we have reduced fee, but no Person(s) to Notify
                $this->data = array_merge($this->data, [
                    'PTNOnly' => false,
                    'FeeFormOnly' => true,
                    'FeeFormPTN' => false,
                    'remission' => true,
                ]);
            }
        } else {
            if (is_null($lpa->payment->reducedFeeLowIncome)) {
                // we do not have reduced fee but we do have Person(s) to Notify
                $this->data = array_merge($this->data, [
                    'PTNOnly' => true,
                    'FeeFormOnly' => false,
                    'FeeFormPTN' => false,
                    'remission' => false,
                ]);
            } else {
                // we have reduced fee and Person(s) to Notify
                $this->data = array_merge($this->data, [
                    'PTNOnly' => false,
                    'FeeFormOnly' => false,
                    'FeeFormPTN' => true,
                    'remission' => true,
                ]);
            }
        }
    }

    public function setUserDetailsSession(Container $userDetailsSession)
    {
        $this->userDetailsSession = $userDetailsSession;
    }
}
