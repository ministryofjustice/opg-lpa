<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Lpa\Traits\CheckoutTrait;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Communication;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CheckoutConfirmHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;

    public function __construct(
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        UrlHelper $urlHelper,
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

        // Sanity check; making sure this method isn't called if there's something to pay.
        if (intval($lpa->payment->amount) !== 0) {
            throw new RuntimeException('Invalid option');
        }

        return $this->finishCheckout($lpa, $request);
    }
}
