<?php

declare(strict_types=1);

namespace App\Service\Payment\Helper;

use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
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

    public function finishCheckout(Lpa $lpa, ServerRequestInterface $request): ResponseInterface
    {
        $this->lpaApplicationService->lockLpa($lpa);
        $this->communicationService->sendRegistrationCompleteEmail($lpa);

        return new RedirectResponse(
            $this->urlHelper->generate('lpa/complete', ['lpa-id' => $lpa->getId()])
        );
    }

    /**
     * Confirms that the payment amount currently associated with the LPA is correct.
     * If the amount has changed, saves the new value and nulls any gateway reference.
     */
    public function verifyLpaPaymentAmount(Lpa $lpa): void
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

                if (!$this->lpaApplicationService->setPayment($lpa, $lpaPayment)) {
                    throw new RuntimeException(
                        'API client failed to set payment details for id: ' . $lpa->getId() . ' in ' . static::class
                    );
                }
            }
        }
    }
}
