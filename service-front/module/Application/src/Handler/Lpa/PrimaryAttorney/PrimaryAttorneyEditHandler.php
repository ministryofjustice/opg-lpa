<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\PrimaryAttorneyHandlerTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PrimaryAttorneyEditHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use PrimaryAttorneyHandlerTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $isPopup = $this->isXmlHttpRequest($request);

        $templateParams = [
            'isPopup' => $isPopup,
        ];

        // Get the attorney index from the route params
        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $attorneyIdx = $params['idx'] ?? null;

        if ($attorneyIdx === null || !array_key_exists($attorneyIdx, $lpa->document->primaryAttorneys)) {
            return new HtmlResponse('Page not found', 404);
        }

        $attorney = $lpa->document->primaryAttorneys[$attorneyIdx];

        // Determine form type based on attorney type
        if ($attorney instanceof Human) {
            /** @var AbstractActorForm $form */
            $form = $this->formElementManager->get('Application\Form\Lpa\AttorneyForm');
            $form->setActorData('attorney', $this->getActorsList($lpa, (int) $attorneyIdx));
            $template = 'application/authenticated/lpa/primary-attorney/person-form.twig';
        } else {
            /** @var AbstractActorForm $form */
            $form = $this->formElementManager->get('Application\Form\Lpa\TrustCorporationForm');
            $template = 'application/authenticated/lpa/primary-attorney/trust-form.twig';
        }

        $form->setAttribute(
            'action',
            $this->urlHelper->generate(
                'lpa/primary-attorney/edit',
                ['lpa-id' => $lpa->id, 'idx' => $attorneyIdx]
            )
        );

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                // Check if this attorney is also the correspondent before updating
                $isCorrespondent = $this->attorneyIsCorrespondent($lpa, $attorney);

                // Update the attorney with new details and transfer across the ID value
                $attorneyId = $attorney->id;
                if ($attorney instanceof Human) {
                    $attorney = new Human($form->getModelDataFromValidatedForm());
                } else {
                    $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                }
                $attorney->id = $attorneyId;

                // Persist to the API
                if (!$this->lpaApplicationService->setPrimaryAttorney($lpa, $attorney, $attorney->id)) {
                    throw new RuntimeException(
                        'API client failed to update a primary attorney ' . $attorneyIdx . ' for id: ' . $lpa->id
                    );
                }

                // Attempt to update the LPA correspondent too if appropriate
                if ($isCorrespondent) {
                    $this->updateCorrespondentData($lpa, $attorney);
                }

                if ($isPopup) {
                    return new JsonResponse(['success' => true]);
                }

                $nextRoute = $flowChecker->nextRoute($currentRoute);

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        $nextRoute,
                        ['lpa-id' => $lpa->id],
                        $flowChecker->getRouteOptions($nextRoute)
                    )
                );
            }
        } else {
            $flattenAttorneyData = $attorney->flatten();

            if ($attorney instanceof Human) {
                $dob = $attorney->dob->date;
                $flattenAttorneyData['dob-date'] = [
                    'day' => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year' => $dob->format('Y'),
                ];
            }

            $form->bind($flattenAttorneyData);
        }

        $templateParams['form'] = $form;

        // Cancel URL
        $templateParams['cancelUrl'] = $this->urlHelper->generate(
            'lpa/primary-attorney',
            ['lpa-id' => $lpa->id]
        );

        $html = $this->renderer->render(
            $template,
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
