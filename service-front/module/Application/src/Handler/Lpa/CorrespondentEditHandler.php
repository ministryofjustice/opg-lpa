<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CorrespondentEditHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ActorReuseDetailsService $actorReuseDetailsService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var User $user */
        $user = $request->getAttribute(RequestAttribute::USER_DETAILS);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $isPopup = $this->isXmlHttpRequest($request);

        $queryParams = $request->getQueryParams();

        // Determine if we are directly editing the existing correspondent
        $editingExistingCorrespondent = (($queryParams['reuse-details'] ?? '') === 'existing-correspondent');

        // On GET, if not directly editing the existing correspondent, check reuse-details options
        if (
            !$editingExistingCorrespondent
            && strtoupper($request->getMethod()) !== RequestMethodInterface::METHOD_POST
        ) {
            $reuseDetailsIndex = $queryParams['reuseDetailsIndex'] ?? null;

            if ($reuseDetailsIndex === null) {
                // Not returning from reuse-details — check whether to redirect
                $reuseDetails = $this->actorReuseDetailsService->getCorrespondentReuseDetails($user, $lpa);
                $reuseCount = count($reuseDetails);

                if ($reuseCount > 1) {
                    $reuseDetailsUrl = $this->urlHelper->generate(
                        'lpa/reuse-details',
                        ['lpa-id' => $lpa->id],
                        ['query' => [
                            'calling-url'    => $request->getUri()->getPath(),
                            'include-trusts' => false,
                            'actor-name'     => 'Correspondent',
                        ]]
                    );

                    return new RedirectResponse($reuseDetailsUrl);
                }
            }
        }

        /** @var \Application\Form\Lpa\CorrespondentForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\CorrespondentForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/correspondent/edit', ['lpa-id' => $lpa->id])
        );

        $backButtonUrl = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            // Check if this is a reuse-details POST ('Use my details' link / single-option reuse)
            $reuseDetailsIndex = $postData['reuse-details'] ?? null;

            if ($reuseDetailsIndex !== null) {
                // Bind selected reuse data to the form
                $reuseDetails = $this->actorReuseDetailsService->getCorrespondentReuseDetails($user, $lpa);

                if ($reuseDetailsIndex == '0' && count($reuseDetails) == 1) {
                    $actorDetailsToReuse = array_pop($reuseDetails);
                    $form->bind($actorDetailsToReuse['data']);
                } elseif (array_key_exists($reuseDetailsIndex, $reuseDetails)) {
                    $form->bind($reuseDetails[$reuseDetailsIndex]['data']);
                }

                // If data is non-editable, process it directly
                if (!$form->isEditable()) {
                    $form->isValid();
                    $correspondentData = $form->getModelDataFromValidatedForm() ?? [];
                    return $this->processCorrespondentData($lpa, $correspondentData, $flowChecker, $isPopup);
                }
            } else {
                // Regular form POST — validate and save
                $form->setData($postData);

                if ($form->isValid()) {
                    $correspondentData = $form->getModelDataFromValidatedForm();
                    $correspondentData['contactDetailsEnteredManually'] = true;

                    return $this->processCorrespondentData($lpa, $correspondentData, $flowChecker, $isPopup);
                }
            }
        } else {
            $reuseDetailsIndex = $queryParams['reuseDetailsIndex'] ?? null;

            if ($reuseDetailsIndex !== null && !$editingExistingCorrespondent) {
                // Returning from the reuse-details selection screen via GET redirect
                $reuseDetails = $this->actorReuseDetailsService->getCorrespondentReuseDetails($user, $lpa);

                if (array_key_exists($reuseDetailsIndex, $reuseDetails)) {
                    $form->bind($reuseDetails[$reuseDetailsIndex]['data']);

                    // If data is non-editable, process it directly
                    if (!$form->isEditable()) {
                        $form->isValid();
                        $correspondentData = $form->getModelDataFromValidatedForm() ?? [];
                        return $this->processCorrespondentData($lpa, $correspondentData, $flowChecker, $isPopup);
                    }
                }

                // Set the back button URL from the callingUrl query param
                $callingUrl = $queryParams['callingUrl'] ?? null;
                if ($callingUrl !== null) {
                    $backButtonUrl = str_replace('add-trust', 'add', $callingUrl);
                }
            } elseif ($editingExistingCorrespondent) {
                // Find the existing correspondent data and bind it to the form
                $existingCorrespondent = $this->getLpaCorrespondent($lpa);

                if (
                    $existingCorrespondent instanceof Correspondence ||
                    $existingCorrespondent instanceof TrustCorporation
                ) {
                    $form->bind($existingCorrespondent->flatten());
                }
            }

            // Add back button URL if reuse details are available and we're not editing existing
            if (!$editingExistingCorrespondent && $backButtonUrl === null) {
                $reuseDetails = $this->actorReuseDetailsService->getCorrespondentReuseDetails($user, $lpa);
                if (count($reuseDetails) > 1) {
                    $backButtonUrl = $this->urlHelper->generate(
                        'lpa/correspondent',
                        ['lpa-id' => $lpa->id]
                    );
                }
            }
        }

        $cancelUrl = $this->urlHelper->generate('lpa/correspondent', ['lpa-id' => $lpa->id]);

        $templateParams = [
            'form'          => $form,
            'cancelUrl'     => $cancelUrl,
            'backButtonUrl' => $backButtonUrl,
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/correspondent/edit.twig',
            array_merge(
                $this->getTemplateVariables($request),
                $templateParams
            )
        );

        return new HtmlResponse($html);
    }

    /**
     * Process the correspondent data and return an appropriate response.
     */
    private function processCorrespondentData(
        Lpa $lpa,
        array $correspondentData,
        FormFlowChecker $flowChecker,
        bool $isPopup
    ): ResponseInterface {
        $lpaCorrespondent = $lpa->document->correspondent;

        // Set aside any data to retain that is not present in the form
        $existingDataToRetain = [];

        if ($lpaCorrespondent instanceof Correspondence) {
            $existingDataToRetain = [
                'contactByPost'  => $lpaCorrespondent->contactByPost,
                'contactInWelsh' => $lpaCorrespondent->contactInWelsh,
            ];
        }

        $lpaCorrespondent = new Correspondence(array_merge($correspondentData, $existingDataToRetain));

        if (!$this->lpaApplicationService->setCorrespondent($lpa, $lpaCorrespondent)) {
            throw new RuntimeException('API client failed to update correspondent for id: ' . $lpa->id);
        }

        if ($isPopup) {
            return new JsonResponse(['success' => true]);
        }

        $nextRoute = $flowChecker->nextRoute('lpa/correspondent/edit');

        return new RedirectResponse(
            $this->urlHelper->generate(
                $nextRoute,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($nextRoute)
            )
        );
    }

    /**
     * Get the best correspondent actor for the LPA.
     *
     * @return \MakeShared\DataModel\AbstractData|null
     */
    private function getLpaCorrespondent(Lpa $lpa)
    {
        $lpaDocument = $lpa->document;
        $correspondent = $lpaDocument->correspondent;

        if (is_null($correspondent)) {
            if ($lpaDocument->whoIsRegistering == Correspondence::WHO_DONOR) {
                $correspondent = $lpaDocument->donor;
            } else {
                $firstAttorneyId = array_values($lpaDocument->whoIsRegistering)[0];

                foreach ($lpaDocument->primaryAttorneys as $attorney) {
                    if ($attorney->id == $firstAttorneyId) {
                        $correspondent = $attorney;
                        break;
                    }
                }
            }
        }

        return $correspondent;
    }
}
