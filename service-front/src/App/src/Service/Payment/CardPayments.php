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
        $payment = $lpa->getPayment();

        return $payment instanceof Payment
            && is_string($payment->getGatewayReference()) && trim($payment->getGatewayReference()) !== ''
            && $payment->getDate() === null && $payment->getMethod() === null
            && $lpa->isLocked() !== true && $lpa->getCompletedAt() === null
            && $lpa->hasFinishedCreation();
    }

    public function recoverCompletedPayment(Lpa $lpa): bool
    {
        if (!$this->isAwaitingConfirmation($lpa)) {
            return false;
        }

        $gatewayReference = $lpa->getPayment()->getGatewayReference();

        try {
            $govPayPayment = $this->paymentClient->getPayment($gatewayReference);

            if ($govPayPayment === null) {
                $this->logger->info('Payment recovery: GOV.UK Pay has no record of this payment', [
                    'lpaId'            => $lpa->getId(),
                    'gatewayReference' => $gatewayReference,
                ]);

                return false;
            }

            if (!$govPayPayment->isSuccess()) {
                $this->logger->info('Payment recovery: outstanding payment did not succeed', [
                    'lpaId'            => $lpa->getId(),
                    'gatewayReference' => $gatewayReference,
                    'status'           => $govPayPayment->state->status ?? null,
                    'finished'         => $govPayPayment->state->finished ?? null,
                ]);

                return false;
            }

            $reference = $govPayPayment->reference ?? null;

            if (!is_string($reference) || trim($reference) === '') {
                $this->logger->warning('Payment recovery: successful payment carries no reference, not recording', [
                    'lpaId'            => $lpa->getId(),
                    'gatewayReference' => $gatewayReference,
                ]);

                return false;
            }

            if (!$this->recordSuccessfulPayment($lpa, $govPayPayment)) {
                return false;
            }
        } catch (Throwable $e) {
            $this->logger->warning('Payment recovery: could not check or record the outstanding payment', [
                'lpaId'            => $lpa->getId(),
                'gatewayReference' => $gatewayReference,
                'exception'        => $e,
            ]);

            return false;
        }

        $this->logger->warning('Payment recovery: recorded a completed GOV.UK Pay payment that was never saved', [
            'lpaId'            => $lpa->getId(),
            'gatewayReference' => $gatewayReference,
            'paymentReference' => $lpa->getPayment()->getReference(),
        ]);

        return true;
    }

    public function recordSuccessfulPayment(Lpa $lpa, GovPayPayment $govPayPayment): bool
    {
        $lpa->getPayment()->setMethod(Payment::PAYMENT_TYPE_CARD);
        $lpa->getPayment()->setReference($govPayPayment->reference);
        $lpa->getPayment()->setDate(new DateTime());

        $govPayEmail = $govPayPayment->email ?? null;

        $lpa->getPayment()->setEmail(is_string($govPayEmail) && trim($govPayEmail) !== ''
            ? new EmailAddress(['address' => strtolower(trim($govPayEmail))])
            : null);

        $result = $this->lpaApplicationService->updateApplication($lpa->getId(), ['payment' => $lpa->getPayment()->toArray()]);

        if ($result === false) {
            $this->logger->critical('PAYMENT RECORDING FAILED — payment taken but LPA not updated', [
                'lpa_id'            => $lpa->getId(),
                'gateway_reference' => $lpa->getPayment()->getGatewayReference(),
                'govpay_status'    => $govPayPayment->state->status ?? 'unknown',
                'has_email'        => $lpa->getPayment()->getEmail() !== null,
            ]);

            return false;
        }

        return true;
    }
}
