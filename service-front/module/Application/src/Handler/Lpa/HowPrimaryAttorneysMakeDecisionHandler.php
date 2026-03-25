<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class HowPrimaryAttorneysMakeDecisionHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE);

        /** @var \Application\Form\Lpa\HowAttorneysMakeDecisionForm $form */
        $form = $this->formElementManager->get(
            'Application\Form\Lpa\HowAttorneysMakeDecisionForm',
            ['lpa' => $lpa]
        );

        // There will be some primary attorney decisions at this
        // point because they will have been created in an earlier step
        $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            if ($postData['how'] != PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                $form->setValidationGroup(['how']);
            }

            $form->setData($postData);

            if ($form->isValid()) {
                /** @var array $formData */
                $formData = $form->getData();
                $howAttorneysAct = $formData['how'];
                $howDetails = null;

                if ($howAttorneysAct == PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS) {
                    $howDetails = $formData['howDetails'];
                }

                if (
                    $primaryAttorneyDecisions->how !== $howAttorneysAct
                    || $primaryAttorneyDecisions->howDetails !== $howDetails
                ) {
                    $primaryAttorneyDecisions->how = $howAttorneysAct;
                    $primaryAttorneyDecisions->howDetails = $howDetails;

                    $setOk = $this->lpaApplicationService->setPrimaryAttorneyDecisions(
                        $lpa,
                        $primaryAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set primary attorney decisions for id: ' . $lpa->id
                        );
                    }

                    $this->replacementAttorneyCleanup->cleanUp($lpa);

                    $this->applicantService->cleanUp($lpa);
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
            $form->bind($primaryAttorneyDecisions->flatten());
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/how-primary-attorneys-make-decision/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
