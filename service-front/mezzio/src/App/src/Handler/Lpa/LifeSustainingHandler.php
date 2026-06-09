<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use Mezzio\Helper\UrlHelper;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
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

class LifeSustainingHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
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

        /** @var \\App\\Form\\Lpa\\LifeSustainingForm $form */
        $form = $this->formElementManager->get(
            'App\Form\Lpa\LifeSustainingForm',
            ['lpa' => $lpa]
        );

        $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                if (!$primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                    $lpa->document->primaryAttorneyDecisions = $primaryAttorneyDecisions;
                }

                /** @var array $formData */
                $formData = $form->getData();
                $canSustainLife = (bool) $formData['canSustainLife'];

                if ($primaryAttorneyDecisions->canSustainLife !== $canSustainLife) {
                    $primaryAttorneyDecisions->canSustainLife = $canSustainLife;

                    $setOk = $this->lpaApplicationService->setPrimaryAttorneyDecisions(
                        $lpa,
                        $primaryAttorneyDecisions
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set life sustaining for id: ' . $lpa->id
                        );
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
            if ($lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $form->bind($lpa->document->primaryAttorneyDecisions->flatten());
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/life-sustaining/index.twig',
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
