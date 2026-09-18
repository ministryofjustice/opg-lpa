<?php

declare(strict_types=1);

namespace App\Handler\Lpa\PrimaryAttorney;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\PrimaryAttorneyHandlerTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PrimaryAttorneyConfirmDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use PrimaryAttorneyHandlerTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly ApplicantService $applicantService,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
        private readonly TemplateRendererInterface $renderer,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $isPopup = $this->isXmlHttpRequest($request);

        // Get the attorney index from the route params
        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $attorneyIdx = $params['idx'] ?? null;

        if ($attorneyIdx === null || !array_key_exists($attorneyIdx, $lpa->document->primaryAttorneys)) {
            return new HtmlResponse('Page not found', 404);
        }

        $attorney = $lpa->document->primaryAttorneys[$attorneyIdx];

        // Setting the trust flag
        $isTrust = isset($attorney->number);

        $conflictError = null;
        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $ifMatchVersion = (int)$postData['version'];
            try {
                // If this attorney is set as the correspondent then delete those details too
                if ($this->attorneyIsCorrespondent($lpa, $attorney)) {
                    $ifMatchVersion = $this->updateCorrespondentData($lpa, $attorney, true, $ifMatchVersion);
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
                        $this->lpaApplicationService->setPrimaryAttorneyDecisions($lpa, $primaryAttorneyDecisions, $ifMatchVersion);
                        $ifMatchVersion++;
                    }
                }

                // If the attorney being removed was set as registering the LPA then remove from there too
                // IMPORTANT - This step is required BEFORE the attorney is removed to ensure
                // that the datamodel validation on the API side does not fail
                $ifMatchVersion = $this->applicantService->removeAttorney($lpa, $attorney->id, $ifMatchVersion);

                // Delete the attorney
                if (!$this->lpaApplicationService->deletePrimaryAttorney($lpa, $attorney->id, $ifMatchVersion)) {
                    throw new RuntimeException(
                        'API client failed to delete a primary attorney ' .
                            $attorneyIdx . ' for id: ' . $lpa->id
                    );
                }

                $this->replacementAttorneyCleanup->cleanUp($lpa, $ifMatchVersion + 1);

                if ($isPopup) {
                    return new JsonResponse(['success' => true]);
                }

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        'lpa/primary-attorney',
                        ['lpa-id' => $lpa->id],
                    )
                );
            } catch (ConflictException $e) {
                $conflictError = $e;
            }
        }

        $templateParams = [
            'attorneyName' => $attorney->name,
            'attorneyAddress' => $attorney->address,
            'isTrust' => $isTrust,
            'isPopup' => $isPopup,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/primary-attorney',
                ['lpa-id' => $lpa->id]
            ),
            'actionUrl' => $this->urlHelper->generate(
                'lpa/primary-attorney/confirm-delete',
                ['lpa-id' => $lpa->id, 'idx' => $attorneyIdx],
            ),
            'conflictError' => $conflictError,
        ];

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
