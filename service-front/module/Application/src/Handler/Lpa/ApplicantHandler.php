<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ApplicantHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE);

        $lpaId = $lpa->id;
        $lpaDocument = $lpa->document;

        $form = $this->formElementManager->get(
            'Application\Form\Lpa\ApplicantForm',
            ['lpa' => $lpa]
        );

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                if (($postData['whoIsRegistering'] ?? '') == Correspondence::WHO_DONOR) {
                    $applicants = Correspondence::WHO_DONOR;
                } else {
                    if (
                        count($lpaDocument->primaryAttorneys) > 1 &&
                        $lpaDocument->primaryAttorneyDecisions->how !=
                        PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
                    ) {
                        $applicants = $form->getData()['attorneyList'];
                    } else {
                        $applicants = explode(',', $form->getData()['whoIsRegistering']);
                    }
                }

                // Save applicant if the value has changed
                if ($applicants != $lpa->document->whoIsRegistering) {
                    if (!$this->lpaApplicationService->setWhoIsRegistering($lpa, $applicants)) {
                        throw new RuntimeException('API client failed to set applicant for id: ' . $lpaId);
                    }
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
            if (is_array($lpaDocument->whoIsRegistering)) {
                if (
                    count($lpaDocument->primaryAttorneys) > 1 &&
                    $lpaDocument->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
                ) {
                    $bindingData = [
                        'whoIsRegistering' => implode(',', array_map(function ($attorney) {
                            return $attorney->id;
                        }, $lpaDocument->primaryAttorneys)),
                        'attorneyList' => $lpaDocument->whoIsRegistering,
                    ];
                } else {
                    $bindingData = ['whoIsRegistering' => implode(',', $lpaDocument->whoIsRegistering)];
                }

                $form->bind($bindingData);
            } else {
                $form->bind(['whoIsRegistering' => $lpaDocument->whoIsRegistering]);
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/applicant/index.twig',
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
