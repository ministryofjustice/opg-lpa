<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Lpa\Traits\CheckoutTrait;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CheckoutChequeHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;

    public function __construct(
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        MvcUrlHelper $urlHelper,
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->communicationService = $communicationService;
        $this->urlHelper = $urlHelper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        if (!$this->isLpaComplete($lpa, $request)) {
            return $this->redirectToMoreInfoRequired($lpa, $request);
        }

        $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

        $this->verifyLpaPaymentAmount($lpa);

        if (!$this->lpaApplicationService->setPayment($lpa, $lpa->payment)) {
            throw new RuntimeException(
                'API client failed to set payment details for id: ' . $lpa->id . ' in ' . static::class
            );
        }

        return $this->finishCheckout($lpa);
    }
}
