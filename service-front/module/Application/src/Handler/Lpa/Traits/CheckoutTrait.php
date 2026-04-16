<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\Traits;

use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Application\Helper\MvcUrlHelper;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

trait CheckoutTrait
{
    private LpaApplicationService $lpaApplicationService;
    private Communication $communicationService;
    private MvcUrlHelper $urlHelper;

    private function isLpaComplete(Lpa $lpa, ServerRequestInterface $request): bool
    {
        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        return $lpa->isStateCreated() && $flowChecker->backToForm() === 'lpa/checkout';
    }

    private function redirectToMoreInfoRequired(Lpa $lpa, ServerRequestInterface $request): ResponseInterface
    {
        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $route = 'lpa/more-info-required';

        return new RedirectResponse(
            $this->urlHelper->generate(
                $route,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($route)
            )
        );
    }

    private function finishCheckout(Lpa $lpa): ResponseInterface
    {
        $this->lpaApplicationService->lockLpa($lpa);
        $this->communicationService->sendRegistrationCompleteEmail($lpa);

        return new RedirectResponse(
            $this->urlHelper->generate('lpa/complete', ['lpa-id' => $lpa->id])
        );
    }

    /**
     * Confirms that the payment amount currently associated with the LPA is correct.
     * If the amount has changed, saves the new value and nulls any gateway reference.
     */
    private function verifyLpaPaymentAmount(Lpa $lpa): void
    {
        $lpaPayment = $lpa->payment;

        if ($lpaPayment instanceof Payment) {
            $existingPaymentAmount = $lpaPayment->amount;

            Calculator::calculate($lpa);

            if ($existingPaymentAmount != $lpaPayment->amount) {
                $lpaPayment->gatewayReference = null;

                if (!$this->lpaApplicationService->setPayment($lpa, $lpaPayment)) {
                    throw new RuntimeException(
                        'API client failed to set payment details for id: ' . $lpa->id . ' in ' . static::class
                    );
                }
            }
        }
    }
}
