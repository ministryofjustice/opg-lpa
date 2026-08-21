<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CheckoutChequeHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private LpaApplicationService $lpaApplicationService,
        private Communication $communicationService,
        private UrlHelper $urlHelper,
        private CheckoutHelper $checkoutHelper
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        if (!$this->checkoutHelper->isLpaComplete($lpa, $request)) {
            return $this->checkoutHelper->redirectToMoreInfoRequired($lpa, $request);
        }

        $lpa->getPayment()->setMethod(Payment::PAYMENT_TYPE_CHEQUE);

        $this->checkoutHelper->verifyLpaPaymentAmount($lpa);

        if (!$this->lpaApplicationService->setPayment($lpa, $lpa->getPayment())) {
            throw new RuntimeException(
                'API client failed to set payment details for id: ' . $lpa->getId() . ' in ' . static::class
            );
        }

        return $this->checkoutHelper->finishCheckout($lpa, $request);
    }
}
