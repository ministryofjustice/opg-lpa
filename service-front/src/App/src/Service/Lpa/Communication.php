<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Model\UserDetailsHolder;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\View\Twig\Traits\MoneyFormatterTrait;
use DateInterval;
use DateTimeZone;
use Exception;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Formatter;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use Psr\Log\LoggerInterface;

class Communication
{
    use MoneyFormatterTrait;

    public const string EMAIL_LPA_REGISTRATION_WITH_PAYMENT1        = 'email-lpa-registration-with-payment1';
    public const string EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2 = 'email-lpa-registration-with-cheque-payment2';
    public const string EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3     = 'email-lpa-registration-with-no-payment3';

    private string $emailTemplateRef;
    private array $data;
    private string $lpaTypeTitleCase;

    public function __construct(
        private readonly MailTransportInterface $mailTransport,
        private readonly UrlHelper $urlHelper,
        private readonly UserDetailsHolder $userDetailsHolder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendRegistrationCompleteEmail(Lpa $lpa): bool|string
    {
        // Get the signed in user's details from UserDetailsHolder (populated by UserDetailsMiddleware).
        $user = $this->userDetailsHolder->get();

        if (!$user instanceof User) {
            $this->logger->error('sendRegistrationCompleteEmail: no user found, cannot send email', [
                'lpaId' => $lpa->getId(),
            ]);
            return 'failed-sending-email';
        }

        $userEmailAddress = ($user->getEmail()?->getAddress() ?? '');
        $to = [$userEmailAddress];

        $this->lpaTypeTitleCase = 'Health and welfare';
        if ($lpa->getDocument()->getType() === Document::LPA_TYPE_PF) {
            $this->lpaTypeTitleCase = 'Property and financial affairs';
        }

        $donorName = '';
        if (!is_null($lpa->getDocument()->getDonor())) {
            $donorName = $lpa->getDocument()->getDonor()->getName()->getFullName();
        }

        $this->data = [
            'donorName' => $donorName,
            'lpaType' => strtolower($this->lpaTypeTitleCase),
            'lpaId' => $this->formatLpaId($lpa->getId()),
            'viewDocsUrl' => $this->url('lpa/view-docs', ['lpa-id' => $lpa->getId()]),
            'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->getId()]),
        ];

        // We use 3 templates, for Cheque payment, Online payment or No payment
        if (!is_null($lpa->getPayment()->getReference())) {
            // we have a payment reference, so this is an online payment
            $to = $this->setUpEmailFieldsForOnlinePayment($lpa, $userEmailAddress, $to);
            $this->setUpEmailFieldsForPayments($lpa);
        } else {
            if ($lpa->getPayment()->getMethod() === 'cheque') {
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
            $this->logger->error('Failed to send registration complete email', [
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
        if (!is_null($lpa->getPayment()->getAmount())) {
            $amount = $this->formatMoney($lpa->getPayment()->getAmount());
        }

        // Assume datetimes are in Europe/London timezone as all our users are in the UK
        $paymentDate = '';
        $refundDate = '';
        if (!is_null($lpa->getPayment()->getDate())) {
            $lpa->getPayment()->getDate()->setTimezone(new DateTimeZone('Europe/London'));
            $paymentDate = $lpa->getPayment()->getDate()->format('j F Y - g:ia');
            $refundDate = $lpa->getPayment()->getDate()->add(new DateInterval('P42D'))->format('j F Y');
        }

        $this->data = array_merge($this->data, [
            'lpaTypeTitleCase' => $this->lpaTypeTitleCase,
            'lpaPaymentReference' => $lpa->getPayment()->getReference(),
            'lpaPaymentDate' => $paymentDate,
            'paymentAmount' => $amount,
            'date' => $refundDate,
        ]);

        // If we have a separate payment address, send the email to that also
        if (!empty($lpa->getPayment()->getEmail()?->getAddress()) && ($lpa->getPayment()->getEmail()->getAddress() != strtolower($userEmailAddress))) {
            $to = array_merge($to, [
                $lpa->getPayment()->getEmail()->getAddress()
            ]);
        }

        return $to;
    }

    public function setUpEmailFieldsForChequePayment(Lpa $lpa): void
    {
        $this->emailTemplateRef = self::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2;

        $amount = '';
        if (!is_null($lpa->getPayment()->getAmount())) {
            $amount = $this->formatMoney($lpa->getPayment()->getAmount());
        }

        $this->data = array_merge($this->data, [
            'feeAmount' => $amount,
        ]);
    }

    public function setUpEmailFieldsForNoPayment(Lpa $lpa): void
    {
        $this->emailTemplateRef = self::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3;

        if (empty($lpa->getDocument()->getPeopleToNotify())) {
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
        if (empty($lpa->getDocument()->getPeopleToNotify())) {
            if (is_null($lpa->getPayment()->isReducedFeeLowIncome())) {
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
            if (is_null($lpa->getPayment()->isReducedFeeLowIncome())) {
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
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $this->urlHelper->generate($routeName, $params);

        return $scheme . '://' . $host . $path;
    }

    private function formatLpaId(int|string $lpaId): string
    {
        return Formatter::id($lpaId);
    }
}
