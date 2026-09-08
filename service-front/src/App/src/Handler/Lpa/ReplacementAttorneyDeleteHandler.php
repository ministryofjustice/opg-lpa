<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ReplacementAttorneyDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
        private readonly LoggerInterface $logger,
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

        // TODO(LPAL-2493): Get version from POST body instead
        $ifMatchVersion = $lpa->getVersion();
        try {
            if ($this->attorneyIsCorrespondent($lpa, $attorney)) {
                if (!$this->lpaApplicationService->deleteCorrespondent($lpa, $ifMatchVersion)) {
                    throw new RuntimeException(
                        'API client failed to delete correspondent for id: ' . $lpa->id
                    );
                }
                $ifMatchVersion++;
            }

            if (!$this->lpaApplicationService->deleteReplacementAttorney($lpa, $attorney->id, $ifMatchVersion)) {
                throw new RuntimeException(
                    'API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpa->id
                );
            }

            $this->replacementAttorneyCleanup->cleanUp($lpa, $ifMatchVersion + 1);

            $route = 'lpa/replacement-attorney';

            return new RedirectResponse(
                $this->urlHelper->generate(
                    $route,
                    ['lpa-id' => $lpa->id],
                    $flowChecker->getRouteOptions($route)
                )
            );
        } catch (ConflictException $e) {
            $this->logger->info('Conflict deleting replacement attorney', ['exception' => $e]);
            throw $e;
        }
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
