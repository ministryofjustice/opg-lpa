<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use Application\Helper\MvcUrlHelper;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IndexHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_CLONE_DATA = 'clone_data';

    public function __construct(
        private readonly Metadata $metadata,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $seedId = (string) $lpa->seed;

        if ($seedId) {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            if ($session instanceof SessionInterface) {
                $this->resetSessionCloneData($session, $seedId);
            }
        }

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

    private function resetSessionCloneData(SessionInterface $session, string $seedId): void
    {
        $cloneData = $session->get(self::SESSION_KEY_CLONE_DATA);

        if (is_array($cloneData) && isset($cloneData[$seedId])) {
            unset($cloneData[$seedId]);
            $session->set(self::SESSION_KEY_CLONE_DATA, $cloneData);
        }
    }
}
