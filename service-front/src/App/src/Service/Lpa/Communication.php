<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Model\UserDetailsHolder;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\View\Twig\Traits\MoneyFormatterTrait;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Formatter;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Helper\UrlHelper;
use DateTimeZone;
use DateInterval;
use Exception;
use Psr\Log\LoggerAwareInterface;

class Communication implements LoggerAwareInterface
{
    use LoggerTrait;
    use MoneyFormatterTrait;

    public const EMAIL_LPA_REGISTRATION_WITH_PAYMENT1        = 'email-lpa-registration-with-payment1';
    public const EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2 = 'email-lpa-registration-with-cheque-payment2';
    public const EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3     = 'email-lpa-registration-with-no-payment3';

    private ?UrlHelper $urlHelper = null;
    private ?UserDetailsHolder $userDetailsHolder = null;
    private string $emailTemplateRef;
    private array $data;
    private string $lpaTypeTitleCase;

    public function __construct(
        private readonly MailTransportInterface $mailTransport,
    ) {
    }

    public function setUrlHelper(UrlHelper $urlHelper): void
    {
        $this->urlHelper = $urlHelper;
    }

    public function setUserDetailsHolder(UserDetailsHolder $userDetailsHolder): void
    {
        $this->userDetailsHolder = $userDetailsHolder;
    }

    public function sendRegistrationCompleteEmail(Lpa $lpa): bool|string
    {
        // Get the signed in user's details from UserDetailsHolder (populated by UserDetailsMiddleware).
        $user = $this->userDetailsHolder?->get();

        if (!$user instanceof User) {
            $this->getLogger()->error('sendRegistrationCompleteEmail: no user found, cannot send email', [
                'lpaId' => $lpa->id,
            ]);
            return 'failed-sending-email';
        }

        $userEmailAddress = (string) ($user->email?->address ?? '');
        $to = [$userEmailAddress];

        $this->lpaTypeTitleCase = 'Health and welfare';
        if ($lpa->document->type === Document::LPA_TYPE_PF) {
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
            'viewDocsUrl' => $this->url('lpa/view-docs', ['lpa-id' => $lpa->id]),
            'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->id]),
        ];

        // We use 3 templates, for Cheque payment, Online payment or No payment
        if (!is_null($lpa->payment->reference)) {
            // we have a payment reference, so this is an online payment
            $to = $this->setUpEmailFieldsForOnlinePayment($lpa, $userEmailAddress, $to);
            $this->setUpEmailFieldsForPayments($lpa);
        } else {
            if ($lpa->payment->method === 'cheque') {
                // we have a cheque payment
                $this->setUpEmailFieldsForChequePayment($lpa);
                $this->setUpEmailFieldsForPayments($lpa);
            } else {
                // we have a zero payment
                $this->setUpEmailFieldsForNoPayment($lpa);
            }
        }

        try {
            $mailParameters = new MailParameters($to, $this->emailTemplateRef, $this->data);
            $this->mailTransport->send($mailParameters);
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to send registration complete email', [
                'exception' => $ex,
            ]);

            return "failed-sending-email";
        }

        return true;
    }

    public function setUpEmailFieldsForOnlinePayment(Lpa $lpa, string $userEmailAddress, array $to): array
    {
        $this->emailTemplateRef = self::EMAIL_LPA_REGISTRATION_WITH_PAYMENT1;

        $amount = '';
        if (isset($lpa->payment->amount)) {
            $amount = $this->formatMoney($lpa->payment->amount);
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

    public function setUpEmailFieldsForChequePayment(Lpa $lpa): void
    {
        $this->emailTemplateRef = self::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2;

        $amount = '';
        if (isset($lpa->payment->amount)) {
            $amount = $this->formatMoney($lpa->payment->amount);
        }

        $this->data = array_merge($this->data, [
            'feeAmount' => $amount,
        ]);
    }

    public function setUpEmailFieldsForNoPayment(Lpa $lpa): void
    {
        $this->emailTemplateRef = self::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3;

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

    public function setUpEmailFieldsForPayments(Lpa $lpa): void
    {
        // fill out email fields appropriately that apply to cheque and online payments
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

    private function url(string $routeName, array $params = []): string
    {
        if ($this->urlHelper === null) {
            return '';
        }

        return $this->urlHelper->generate($routeName, $params);
    }

    private function formatLpaId(int|string $lpaId): string
    {
        return Formatter::id($lpaId);
    }

    public function getMailTransport(): MailTransportInterface
    {
        return $this->mailTransport;
    }
}
