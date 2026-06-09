<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use Mezzio\Helper\UrlHelper;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class WhenReplacementAttorneyStepInHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        /** @var \\App\\Form\\Lpa\\WhenReplacementAttorneyStepInForm $form */
        $form = $this->formElementManager->get(
            'App\Form\Lpa\WhenReplacementAttorneyStepInForm',
            ['lpa' => $lpa]
        );

        $replacementAttorneyDecisions = $lpa->document->replacementAttorneyDecisions;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            if (($postData['when'] ?? '') != ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                $form->setValidationGroup(['when']);
            }

            $form->setData($postData);

            if ($form->isValid()) {
                if (!$replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                    $replacementAttorneyDecisions = new ReplacementAttorneyDecisions();
                    $lpa->document->replacementAttorneyDecisions = $replacementAttorneyDecisions;
                }

                /** @var array $formData */
                $formData = $form->getData();
                $whenReplacementStepIn = $formData['when'];
                $whenDetails = null;

                if ($whenReplacementStepIn == ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS) {
                    $whenDetails = $formData['whenDetails'];
                }

                if (
                    $replacementAttorneyDecisions->when !== $whenReplacementStepIn ||
                    $replacementAttorneyDecisions->whenDetails !== $whenDetails
                ) {
                    $replacementAttorneyDecisions->when = $whenReplacementStepIn;
                    $replacementAttorneyDecisions->whenDetails = $whenDetails;

                    $setOk = $this->lpaApplicationService->setReplacementAttorneyDecisions(
                        $lpa,
                        $replacementAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set replacement step in decisions for id: ' . $lpa->id
                        );
                    }
                }

                $this->replacementAttorneyCleanup->cleanUp($lpa);

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
            if ($replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                $form->bind($replacementAttorneyDecisions->flatten());
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/when-replacement-attorney-step-in/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'      => $form,
                    'csrfToken' => $csrfToken,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
