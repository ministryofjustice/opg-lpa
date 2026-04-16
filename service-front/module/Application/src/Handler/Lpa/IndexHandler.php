<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IndexHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly Metadata $metadata,
        private readonly MvcUrlHelper $urlHelper,
        private readonly SessionManagerSupport $sessionManagerSupport,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $seedId = (string) $lpa->seed;

        if ($seedId) {
            $this->resetSessionCloneData($seedId);
        }

        // We want to track the number of times an LPA has been 'worked on'.
        // Which is defined by the number of times this method is called, per LPA.

        // Get the current count and increment by 1
        $analyticsReturnCount = (isset($lpa->metadata['analyticsReturnCount']) ? $lpa->metadata['analyticsReturnCount'] : 0);
        $analyticsReturnCount++;

        $this->metadata->setAnalyticsReturnCount($lpa, $analyticsReturnCount);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $destinationRoute = $flowChecker->backToForm();

        return new RedirectResponse(
            $this->urlHelper->generate(
                $destinationRoute,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($destinationRoute)
            )
        );
    }

    private function resetSessionCloneData(string $seedId): void
    {
        $session = $this->sessionManagerSupport->getSessionManager()->getStorage();

        if (isset($session['cloneData'][$seedId])) {
            unset($session['cloneData'][$seedId]);
        }
    }
}
