<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ReplacementAttorneyDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $attorneyIdx = $routeResult ? $routeResult->getMatchedParams()['idx'] ?? null : null;

        if (!array_key_exists($attorneyIdx, $lpa->document->replacementAttorneys)) {
            return new HtmlResponse('', 404);
        }

        $attorney = $lpa->document->replacementAttorneys[$attorneyIdx];

        if ($this->attorneyIsCorrespondent($lpa, $attorney)) {
            if (!$this->lpaApplicationService->deleteCorrespondent($lpa)) {
                throw new RuntimeException(
                    'API client failed to delete correspondent for id: ' . $lpa->id
                );
            }
        }

        if (!$this->lpaApplicationService->deleteReplacementAttorney($lpa, $attorney->id)) {
            throw new RuntimeException(
                'API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpa->id
            );
        }

        $this->replacementAttorneyCleanup->cleanUp($lpa);

        $route = 'lpa/replacement-attorney';

        return new RedirectResponse(
            $this->urlHelper->generate(
                $route,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($route)
            )
        );
    }

    private function attorneyIsCorrespondent(Lpa $lpa, AbstractAttorney $attorney): bool
    {
        $correspondent = $lpa->document->correspondent;

        if ($correspondent instanceof Correspondence && $correspondent->who === Correspondence::WHO_ATTORNEY) {
            $nameToCompare = ($attorney instanceof TrustCorporation
                ? $correspondent->company
                : $correspondent->name);

            return ($attorney->name == $nameToCompare && $attorney->address == $correspondent->address);
        }

        return false;
    }
}
