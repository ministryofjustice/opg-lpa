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

        // Sanity check; making sure this method isn't called if there's something to pay.
        if (intval($lpa->payment->amount) !== 0) {
            throw new RuntimeException('Invalid option');
        }

        return $this->finishCheckout($lpa);
    }
}
