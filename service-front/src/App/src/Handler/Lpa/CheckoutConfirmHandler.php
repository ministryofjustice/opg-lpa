<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use App\Service\Payment\Helper\CheckoutHelper;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CheckoutConfirmHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly Communication $communicationService,
        private readonly UrlHelper $urlHelper,
        private readonly CheckoutHelper $checkoutHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        if (!$this->checkoutHelper->isLpaComplete($lpa, $request)) {
            return $this->checkoutHelper->redirectToMoreInfoRequired($lpa, $request);
        }

        // Sanity check; making sure this method isn't called if there's something to pay.
        if (intval($lpa->getPayment()->getAmount()) !== 0) {
            throw new RuntimeException('Invalid option');
        }

        return $this->checkoutHelper->finishCheckout($lpa, $request);
    }
}
