<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutChequeHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private LpaApplicationService $lpaApplicationService,
        private Communication $communicationService,
        private UrlHelper $urlHelper,
        private CheckoutHelper $checkoutHelper,
        private LoggerInterface $logger,
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

        // TODO(LPAL-2493): Get version from POST body instead
        $ifMatchVersion = $lpa->getVersion();
        try {
            $ifMatchVersion = $this->checkoutHelper->verifyLpaPaymentAmount($lpa, $ifMatchVersion);

            if (!$this->lpaApplicationService->setPayment($lpa, $lpa->getPayment(), $ifMatchVersion)) {
                throw new RuntimeException(
                    'API client failed to set payment details for id: ' . $lpa->getId() . ' in ' . static::class
                );
            }

            return $this->checkoutHelper->finishCheckout($lpa, $request, $ifMatchVersion + 1);
        } catch (ConflictException $e) {
            $this->logger->info('Conflict checking out with cheque', ['exception' => $e]);
            throw $e;
        }
    }
}
