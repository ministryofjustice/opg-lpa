<?php

declare(strict_types=1);

namespace App\Service\Payment\Helper;

use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\CardPayments;
use App\Service\Payment\GovPay\Client as GovPayClient;
use GuzzleHttp\Psr7\Uri;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutHelper
{
    public const int LPA_ID_LENGTH = 11;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly Communication $communicationService,
        private readonly UrlHelper $urlHelper,
        private readonly GovPayClient $paymentClient,
        private readonly CardPayments $cardPayments,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function constructPaymentTransactionId(string $lpaId): string
    {
        if (strlen($lpaId) > self::LPA_ID_LENGTH) {
            throw new \Exception('LPA ID is too long');
        }

        return str_pad($lpaId, self::LPA_ID_LENGTH, '0', STR_PAD_LEFT);
    }

    public function isLpaComplete(Lpa $lpa, ServerRequestInterface $request): bool
    {
        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        return $lpa->isStateCreated() && $flowChecker->backToForm() === 'lpa/checkout';
    }

    public function redirectToMoreInfoRequired(Lpa $lpa, ServerRequestInterface $request): ResponseInterface
    {
        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $route = 'lpa/more-info-required';

        return new RedirectResponse(
            $this->urlHelper->generate(
                $route,
                ['lpa-id' => $lpa->getId()],
                $flowChecker->getRouteOptions($route)
            )
        );
    }

    public function finishCheckout(Lpa $lpa, ServerRequestInterface $request, int $ifMatchVersion): ResponseInterface
    {
        $this->lpaApplicationService->lockLpa($lpa, $ifMatchVersion);
        $this->communicationService->sendRegistrationCompleteEmail($lpa);

        return new RedirectResponse(
            $this->urlHelper->generate('lpa/complete', ['lpa-id' => $lpa->getId()])
        );
    }

    /**
     * Confirms that the payment amount currently associated with the LPA is correct.
     * If the amount has changed, saves the new value and nulls any gateway reference.
     */
    public function verifyLpaPaymentAmount(Lpa $lpa, int $ifMatchVersion): int
    {
        $lpaPayment = $lpa->getPayment();

        if ($lpaPayment instanceof Payment) {
            $existingPaymentAmount = $lpaPayment->getAmount();

            Calculator::calculate($lpa);

            if ($existingPaymentAmount !== $lpaPayment->getAmount()) {
                $this->logger->info('LPA Payment amount does not match current payment amount', [
                    'lpa_id'            => $lpa->getId(),
                    'current_amount'    => $existingPaymentAmount,
                    'calculated_amount' => $lpaPayment->getAmount(),
                ]);

                $lpaPayment->setGatewayReference(null);

                if (!$this->lpaApplicationService->setPayment($lpa, $lpaPayment, $ifMatchVersion)) {
                    throw new RuntimeException(
                        'API client failed to set payment details for id: ' . $lpa->getId() . ' in ' . static::class
                    );
                }

                return $ifMatchVersion + 1;
            }
        }

        return $ifMatchVersion;
    }

    public function confirmAndPayByCheque(Lpa $lpa, ServerRequestInterface $request, int $ifMatchVersion)
    {
        $lpa->getPayment()->setMethod(Payment::PAYMENT_TYPE_CHEQUE);

        $ifMatchVersion = $this->verifyLpaPaymentAmount($lpa, $ifMatchVersion);

        if (!$this->lpaApplicationService->setPayment($lpa, $lpa->getPayment(), $ifMatchVersion)) {
            throw new RuntimeException(
                'API client failed to set payment details for id: ' . $lpa->getId() . ' in ' . static::class
            );
        }

        return $this->finishCheckout($lpa, $request, $ifMatchVersion + 1);
    }

    public function confirmAndPayByCard(Lpa $lpa, ServerRequestInterface $request, int $ifMatchVersion)
    {
        $ifMatchVersion = $this->verifyLpaPaymentAmount($lpa, $ifMatchVersion);

        // Check for any existing payments in play
        if (!is_null($lpa->getPayment()->getGatewayReference())) {
            $gatewayReference = $lpa->getPayment()->getGatewayReference();
            $payment          = $this->paymentClient->getPayment($gatewayReference);

            if (is_null($payment)) {
                throw new RuntimeException(
                    'Invalid GovPay payment reference: ' . $gatewayReference
                );
            }

            if ($payment->isSuccess()) {
                // Payment already completed — record it and finish.
                $this->cardPayments->recordSuccessfulPayment($lpa, $payment, $ifMatchVersion);

                $this->logger->info('user returned to checkout with successful payment and updated LPA', [
                    'lpa_id'            => $lpa->getId(),
                    'gateway_reference' => $gatewayReference,
                    'payment_method'    => $lpa->getPayment()->getMethod(),
                    'has_email'         => $lpa->getPayment()->getEmail()?->getAddress() !== '',
                ]);

                return $this->finishCheckout($lpa, $request, $ifMatchVersion + 1);
            }

            if (!$payment->isFinished()) {
                return new RedirectResponse((string) $payment->getPaymentPageUrl());
            }
        }

        // Create a new payment
        $ref = self::constructPaymentTransactionId((string) $lpa->getId());

        $description = $lpa->getDocument()->getType() == 'property-and-financial'
            ? 'Property and financial affairs'
            : 'Health and welfare';
        $description .= ' LPA for ' . $lpa->getDocument()->getDonor()->getName()->getFullName();

        // Build the callback URL using the request URI
        $requestUri = $request->getUri();
        $baseUrl = $requestUri->getScheme() . '://' . $requestUri->getAuthority();
        $callback = $baseUrl . $this->urlHelper->generate(
            'lpa/checkout/pay/response',
            ['lpa-id' => $lpa->getId()]
        );

        $payment = $this->paymentClient->createPayment(
            (int) ($lpa->getPayment()->getAmount() * 100.0), // amount in pence
            $ref,
            $description,
            new Uri($callback)
        );

        /** @psalm-suppress UndefinedPropertyFetch */
        $lpa->getPayment()->setGatewayReference($payment->payment_id);

        $this->logger->info('payment created with GOV UK Pay', [
            'lpa_id'            => $lpa->getId(),
            'gateway_reference' => $lpa->getPayment()->getGatewayReference(),
        ]);

        $this->lpaApplicationService->updateApplication($lpa->getId(), ['payment' => $lpa->getPayment()->toArray()], $ifMatchVersion);

        $this->logger->info('LPA updated with payment information, redirecting to gov.uk pay', [
            'lpa_id'   => $lpa->getId(),
            'payment' => $lpa->getPayment()->toJson(),
        ]);

        return new RedirectResponse((string) $payment->getPaymentPageUrl());
    }

    public function confirmAndPayNothing(Lpa $lpa, ServerRequestInterface $request, int $ifMatchVersion)
    {
        // Sanity check; making sure this method isn't called if there's something to pay.
        if (intval($lpa->getPayment()->getAmount()) !== 0) {
            throw new RuntimeException('Invalid option');
        }

        return $this->finishCheckout($lpa, $request, $ifMatchVersion);
    }
}
