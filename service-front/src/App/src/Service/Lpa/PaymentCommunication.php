<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Model\UserDetailsHolder;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\View\Twig\Traits\MoneyFormatterTrait;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Formatter;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use Psr\Log\LoggerInterface;

class PaymentCommunication
{
    use MoneyFormatterTrait;

    public const string EMAIL_LPA_REGISTRATION_WITH_PAYMENT        = 'email-lpa-registration-with-payment';
    public const string EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT = 'email-lpa-registration-with-cheque-payment';
    public const string EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT     = 'email-lpa-registration-with-no-payment';
    public const string EMAIL_LPA_REGISTRATION_WITH_PAYMENT_PAYER  = 'email-lpa-registration-with-payment-payer';

    public function __construct(
        private readonly MailTransportInterface $mailTransport,
        private readonly UrlHelper $urlHelper,
        private readonly UserDetailsHolder $userDetailsHolder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendRegistrationCompleteEmail(Lpa $lpa): void
    {
        // Get the signed in user's details from UserDetailsHolder (populated by UserDetailsMiddleware).
        $user = $this->userDetailsHolder->get();

        if (!$user instanceof User) {
            $this->logger->error('sendRegistrationCompleteEmail: no user found, cannot send email', [
                'lpa_id' => $lpa->getId(),
            ]);
            return;
        }

        switch ($lpa->getPayment()->getMethod()) {
            case Payment::PAYMENT_TYPE_CARD:
                $this->sendOnlinePayerEmail($user, $lpa);
                $this->sendOnlinePaymentEmail($user, $lpa);
                break;

            case Payment::PAYMENT_TYPE_CHEQUE:
                $this->sendChequePaymentEmail($user, $lpa);
                break;

            default:
                $this->sendNoPaymentEmail($user, $lpa);
        }
    }

    private function sendOnlinePayerEmail(User $user, Lpa $lpa): void
    {
        $userEmailAddress = $user->getEmail()->getAddress();
        $paymentEmailAddress = $lpa->getPayment()->getEmail()->getAddress();

        if ($paymentEmailAddress === strtolower($userEmailAddress)) {
            return;
        }

        $mailParameters = new MailParameters(
            $paymentEmailAddress,
            self::EMAIL_LPA_REGISTRATION_WITH_PAYMENT_PAYER,
            [
                'donorName' => $lpa->getDocument()->getDonor()->getName()->getFullName(),
                'lpaType' => $this->formatLpaType($lpa),
                'lpaId' => $this->formatLpaId($lpa),
                'lpaPaymentReference' => $lpa->getPayment()->getReference(),
                'lpaPaymentDate' => $this->formatPaymentDate($lpa),
                'paymentAmount' => $this->formatMoney($lpa->getPayment()->getAmount()),
                'date' => $this->formatRefundDate($lpa),
            ],
        );

        try {
            $this->mailTransport->send($mailParameters);
        } catch (Exception $ex) {
            $this->logger->error('Failed to send payee email', [
                'exception' => $ex,
            ]);
        }
    }

    private function sendOnlinePaymentEmail(User $user, Lpa $lpa): void
    {
        $hasPTN = $this->hasPeopleToNotify($lpa);
        $isReducedFee = $lpa->getPayment()->isReducedFeeLowIncome() === true;

        $mailParameters = new MailParameters(
            $user->getEmail()->getAddress(),
            self::EMAIL_LPA_REGISTRATION_WITH_PAYMENT,
            [
                'donorName'           => $lpa->getDocument()->getDonor()->getName()->getFullName(),
                'lpaType'             => $this->formatLpaType($lpa),
                'lpaId'               => $this->formatLpaId($lpa),
                'lpaPaymentReference' => $lpa->getPayment()->getReference(),
                'lpaPaymentDate'      => $this->formatPaymentDate($lpa),
                'paymentAmount'       => $this->formatMoney($lpa->getPayment()->getAmount()),
                'date'                => $this->formatRefundDate($lpa),
                'feeFormPTN'          => $hasPTN && $isReducedFee,
                'feeFormOnly'         => !$hasPTN && $isReducedFee,
                'PTNOnly'             => $hasPTN && !$isReducedFee,
                'remission'           => $isReducedFee,
                'viewDocsUrl'         => $this->url('lpa/view-docs', ['lpa-id' => $lpa->getId()]),
                'checkDatesUrl'       => $this->url('lpa/date-check', ['lpa-id' => $lpa->getId()]),
            ],
        );

        try {
            $this->mailTransport->send($mailParameters);
        } catch (Exception $ex) {
            $this->logger->error('Failed to send online payment email', [
                'exception' => $ex,
            ]);
        }
    }

    private function sendChequePaymentEmail(User $user, Lpa $lpa): void
    {
        $hasPTN = $this->hasPeopleToNotify($lpa);
        $isReducedFee = $lpa->getPayment()->isReducedFeeLowIncome() === true;

        $mailParameters = new MailParameters(
            $user->getEmail()->getAddress(),
            self::EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT,
            [
                'donorName'     => $lpa->getDocument()->getDonor()->getName()->getFullName(),
                'lpaType'       => $this->formatLpaType($lpa),
                'lpaId'         => $this->formatLpaId($lpa),
                'feeAmount'     => $this->formatMoney($lpa->getPayment()->getAmount()),
                'feeFormPTN'    => $hasPTN && $isReducedFee,
                'feeFormOnly'   => !$hasPTN && $isReducedFee,
                'PTNOnly'       => $hasPTN && !$isReducedFee,
                'remission'     => $isReducedFee,
                'viewDocsUrl'   => $this->url('lpa/view-docs', ['lpa-id' => $lpa->getId()]),
                'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->getId()]),
            ],
        );

        try {
            $this->mailTransport->send($mailParameters);
        } catch (Exception $ex) {
            $this->logger->error('Failed to send cheque payment email', [
                'exception' => $ex,
            ]);
        }
    }

    private function sendNoPaymentEmail(User $user, Lpa $lpa): void
    {
        $mailParameters = new MailParameters(
            $user->getEmail()->getAddress(),
            self::EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT,
            [
                'donorName' => $lpa->getDocument()->getDonor()->getName()->getFullName(),
                'lpaType' => $this->formatLpaType($lpa),
                'lpaId' => $this->formatLpaId($lpa),
                'PTN' => $this->hasPeopleToNotify($lpa),
                'viewDocsUrl' => $this->url('lpa/view-docs', ['lpa-id' => $lpa->getId()]),
                'checkDatesUrl' => $this->url('lpa/date-check', ['lpa-id' => $lpa->getId()]),
            ],
        );

        try {
            $this->mailTransport->send($mailParameters);
        } catch (Exception $ex) {
            $this->logger->error('Failed to send no payment email', [
                'exception' => $ex,
            ]);
        }
    }

    private function url(string $routeName, array $params = []): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $this->urlHelper->generate($routeName, $params);

        return $scheme . '://' . $host . $path;
    }

    private function formatLpaId(Lpa $lpa): string
    {
        return Formatter::id($lpa->getId());
    }

    private function formatPaymentDate(Lpa $lpa): string
    {
        return DateTimeImmutable::createFromMutable($lpa->getPayment()->getDate())
            ->setTimezone(new DateTimeZone('Europe/London'))
            ->format('j F Y - g:ia');
    }

    private function formatRefundDate(Lpa $lpa): string
    {
        return DateTimeImmutable::createFromMutable($lpa->getPayment()->getDate())
            ->setTimezone(new DateTimeZone('Europe/London'))
            ->add(new DateInterval('P42D'))
            ->format('j F Y');
    }

    private function formatLpaType(Lpa $lpa): string
    {
        return match ($lpa->getDocument()->getType()) {
            Document::LPA_TYPE_PF => 'property and financial affairs',
            Document::LPA_TYPE_HW => 'health and welfare',
        };
    }

    private function hasPeopleToNotify(Lpa $lpa): bool
    {
        return !empty($lpa->getDocument()->getPeopleToNotify());
    }
}
