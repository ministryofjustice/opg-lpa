<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Payment\GovPay\Client as GovPayClient;
use App\Service\Payment\GovPay\Response\Payment as GovPayPayment;
use DateTime;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Records confirmed GOV Pay card payments against an LPA, and recovers payments that
 * succeeded at GOV.UK Pay but were never recorded.
 *
 * The normal flow records the payment when GOV Pay redirects the user back to
 * /checkout/pay/response. If the user never comes back — they close the tab as soon as
 * the payment completes, or the browser drops the redirect — the payment is taken but the
 * LPA is left unlocked and the user cannot proceed.
 *
 * @psalm-suppress UndefinedPropertyFetch  Lpa/Payment expose their fields via AbstractData
 */
class CardPayments
{
    public function __construct(
        private readonly GovPayClient $paymentClient,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isAwaitingConfirmation(Lpa $lpa): bool
    {
        $payment = $lpa->payment;

        if (!$payment instanceof Payment) {
            return false;
        }

        if (!is_string($payment->gatewayReference) || trim($payment->gatewayReference) === '') {
            return false;
        }

        if ($payment->date !== null || $payment->method !== null) {
            return false;
        }

        if ($lpa->locked === true || $lpa->completedAt !== null) {
            return false;
        }

        return $lpa->hasFinishedCreation();
    }

    public function recoverCompletedPayment(Lpa $lpa): bool
    {
        if (!$this->isAwaitingConfirmation($lpa)) {
            return false;
        }

        $gatewayReference = $lpa->payment->gatewayReference;

        try {
            $govPayPayment = $this->paymentClient->getPayment($gatewayReference);

            if ($govPayPayment === null) {
                $this->logger->info('Payment recovery: GOV.UK Pay has no record of this payment', [
                    'lpaId'            => $lpa->id,
                    'gatewayReference' => $gatewayReference,
                ]);

                return false;
            }

            if (!$govPayPayment->isSuccess()) {
                $this->logger->info('Payment recovery: outstanding payment did not succeed', [
                    'lpaId'            => $lpa->id,
                    'gatewayReference' => $gatewayReference,
                    'status'           => $govPayPayment->state->status ?? null,
                    'finished'         => $govPayPayment->state->finished ?? null,
                ]);

                return false;
            }

            $reference = $govPayPayment->reference ?? null;

            if (!is_string($reference) || trim($reference) === '') {
                $this->logger->warning('Payment recovery: successful payment carries no reference, not recording', [
                    'lpaId'            => $lpa->id,
                    'gatewayReference' => $gatewayReference,
                ]);

                return false;
            }

            if (!$this->recordSuccessfulPayment($lpa, $govPayPayment)) {
                return false;
            }
        } catch (Throwable $e) {
            $this->logger->warning('Payment recovery: could not check or record the outstanding payment', [
                'lpaId'            => $lpa->id,
                'gatewayReference' => $gatewayReference,
                'exception'        => $e,
            ]);

            return false;
        }

        $this->logger->warning('Payment recovery: recorded a completed GOV.UK Pay payment that was never saved', [
            'lpaId'            => $lpa->id,
            'gatewayReference' => $gatewayReference,
            'paymentReference' => $lpa->payment->reference,
        ]);

        return true;
    }

    public function recordSuccessfulPayment(Lpa $lpa, GovPayPayment $govPayPayment): bool
    {
        $lpa->payment->method    = Payment::PAYMENT_TYPE_CARD;
        $lpa->payment->reference = $govPayPayment->reference;
        $lpa->payment->date      = new DateTime();

        $govPayEmail = $govPayPayment->email ?? null;

        $lpa->payment->email = is_string($govPayEmail) && trim($govPayEmail) !== ''
            ? new EmailAddress(['address' => strtolower(trim($govPayEmail))])
            : null;

        $result = $this->lpaApplicationService->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

        if ($result === false) {
            $this->logger->critical('PAYMENT RECORDING FAILED — payment taken but LPA not updated', [
                'lpaId'            => $lpa->id,
                'gatewayReference' => $lpa->payment->gatewayReference,
                'govpay_status'    => $govPayPayment->state->status ?? 'unknown',
                'has_email'        => $lpa->payment->email !== null,
            ]);

            return false;
        }

        return true;
    }
}
