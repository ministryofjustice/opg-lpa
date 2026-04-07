<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PrimaryAttorney;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\PrimaryAttorneyHandlerTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PrimaryAttorneyDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use PrimaryAttorneyHandlerTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ApplicantService $applicantService,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        // Get the attorney index from the route params
        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $attorneyIdx = $params['idx'] ?? null;

        if ($attorneyIdx === null || !array_key_exists($attorneyIdx, $lpa->document->primaryAttorneys)) {
            return new HtmlResponse('Page not found', 404);
        }

        $attorney = $lpa->document->primaryAttorneys[$attorneyIdx];

        // If this attorney is set as the correspondent then delete those details too
        if ($this->attorneyIsCorrespondent($lpa, $attorney)) {
            $this->updateCorrespondentData($lpa, $attorney, true);
        }

        // If the deletion of the attorney means there are no longer multiple
        // attorneys then reset the how decisions
        if (count($lpa->document->primaryAttorneys) <= 2) {
            $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;

            if (
                $primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions &&
                $primaryAttorneyDecisions->how !== null
            ) {
                $primaryAttorneyDecisions->how = null;
                $primaryAttorneyDecisions->howDetails = null;
                $this->lpaApplicationService->setPrimaryAttorneyDecisions($lpa, $primaryAttorneyDecisions);
            }
        }

        // If the attorney being removed was set as registering the LPA then remove from there too
        // IMPORTANT - This step is required BEFORE the attorney is removed to ensure
        // that the datamodel validation on the API side does not fail
        $this->applicantService->removeAttorney($lpa, $attorney->id);

        // Delete the attorney
        if (!$this->lpaApplicationService->deletePrimaryAttorney($lpa, $attorney->id)) {
            throw new RuntimeException(
                'API client failed to delete a primary attorney ' .
                $attorneyIdx . ' for id: ' . $lpa->id
            );
        }

        $this->replacementAttorneyCleanup->cleanUp($lpa);

        $route = 'lpa/primary-attorney';

        return new RedirectResponse(
            $this->urlHelper->generate(
                $route,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($route)
            )
        );
    }
}
