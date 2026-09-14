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
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutConfirmHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly Communication $communicationService,
        private readonly UrlHelper $urlHelper,
        private readonly CheckoutHelper $checkoutHelper,
        private readonly LoggerInterface $logger,
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

        // TODO(LPAL-2493): Get version from POST body instead
        $ifMatchVersion = $lpa->getVersion();
        try {
            return $this->checkoutHelper->finishCheckout($lpa, $request, $ifMatchVersion);
        } catch (ConflictException $e) {
            $this->logger->info('Conflict confirming check out', ['exception' => $e]);
            throw $e;
        }
    }
}
