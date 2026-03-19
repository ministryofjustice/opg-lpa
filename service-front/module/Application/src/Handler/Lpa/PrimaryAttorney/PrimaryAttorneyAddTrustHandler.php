<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\PrimaryAttorneyHandlerTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionUtility;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PrimaryAttorneyAddTrustHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use PrimaryAttorneyHandlerTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ApplicantService $applicantService,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
        private readonly SessionUtility $sessionUtility,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE);

        $isPopup = $this->isXmlHttpRequest($request);

        // Redirect to human add attorney if trusts are not allowed
        if (!$this->allowTrust($lpa)) {
            $route = 'lpa/primary-attorney/add';
            return new RedirectResponse(
                $this->urlHelper->generate(
                    $route,
                    ['lpa-id' => $lpa->id],
                    $flowChecker->getRouteOptions($route)
                )
            );
        }

        /** @var AbstractActorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\TrustCorporationForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/primary-attorney/add-trust', ['lpa-id' => $lpa->id])
        );

        // Handle reuse details redirected back from ReuseDetailsController (via query params)
        $queryParams = $request->getQueryParams();
        $reuseDetailsIndexFromQuery = $queryParams['reuseDetailsIndex'] ?? null;
        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if (!$isPost && $reuseDetailsIndexFromQuery !== null) {
            $reuseDetails = $this->getSeedReuseDetails($lpa);
            if (array_key_exists($reuseDetailsIndexFromQuery, $reuseDetails)) {
                $form->bind($reuseDetails[$reuseDetailsIndexFromQuery]['data']);
            }
        }

        if ($isPost) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $addOk = $this->lpaApplicationService->addPrimaryAttorney(
                    $lpa,
                    new TrustCorporation($form->getModelDataFromValidatedForm())
                );

                if (!$addOk) {
                    throw new RuntimeException(
                        'API client failed to add a trust corporation attorney for id: ' . $lpa->id
                    );
                }

                $this->replacementAttorneyCleanup->cleanUp($lpa);
                $this->applicantService->cleanUp($lpa);

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
        }

        // Add reuse details back button
        if (isset($queryParams['callingUrl'])) {
            $templateParams['backButtonUrl'] = str_replace(
                'add-trust',
                'add',
                $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpa->id])
            );
        }

        $templateParams = array_merge($templateParams, [
            'isPopup' => $isPopup,
            'form' => $form,
            'switchAttorneyTypeRoute' => 'lpa/primary-attorney/add',
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/primary-attorney',
                ['lpa-id' => $lpa->id]
            ),
        ]);

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/trust-form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }

    /**
     * Fetch seed LPA reuse details from session/API.
     */
    private function getSeedReuseDetails(Lpa $lpa): array
    {
        $seedId = (string) $lpa->seed;

        if (empty($seedId)) {
            return [];
        }

        $sessionSeedData = $this->sessionUtility->getFromMvc('clone', $seedId);
        $seedDetails = [];

        if ($sessionSeedData === null) {
            $seedDetails = $this->lpaApplicationService->getSeedDetails($lpa->id);
            $this->sessionUtility->setInMvc('clone', $seedId, $seedDetails);
        } elseif (is_array($sessionSeedData)) {
            $seedDetails = $sessionSeedData;
        }

        $reuseDetails = [];

        foreach ($seedDetails as $type => $actorData) {
            if ($type === 'primaryAttorneys' || $type === 'replacementAttorneys') {
                $suffixText = $type === 'primaryAttorneys'
                    ? '(was a primary attorney)'
                    : '(was a replacement attorney)';
                foreach ($actorData as $singleActorData) {
                    $isTrust = (($singleActorData['type'] ?? '') === 'trust');
                    if ($isTrust) {
                        $reuseDetails['t'] = [
                            'label' => trim(($singleActorData['name'] ?? '') . ' ' . $suffixText),
                            'data' => $singleActorData,
                        ];
                    }
                }
            }
        }

        return $reuseDetails;
    }
}
