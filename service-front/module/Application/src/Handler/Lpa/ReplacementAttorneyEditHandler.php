<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Form\Lpa\AttorneyForm;
use Application\Form\Lpa\TrustCorporationForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ReplacementAttorneyEditHandler implements RequestHandlerInterface
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

        /** @var \Application\Model\FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $isPopup = $this->isXmlHttpRequest($request);

        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $attorneyIdx = $routeResult ? $routeResult->getMatchedParams()['idx'] ?? null : null;

        if (!array_key_exists($attorneyIdx, $lpa->document->replacementAttorneys)) {
            return $this->notFound();
        }

        $attorney = $lpa->document->replacementAttorneys[$attorneyIdx];

        if ($attorney instanceof Human) {
            /** @var AttorneyForm $form */
            $form = $this->formElementManager->get('Application\Form\Lpa\AttorneyForm');
            $form->setActorData(
                'replacement attorney',
                $this->actorReuseDetailsService->getActorsList($lpa, false, (int) $attorneyIdx)
            );
            $template = 'application/authenticated/lpa/replacement-attorney/person-form.twig';
        } else {
            /** @var TrustCorporationForm $form */
            $form = $this->formElementManager->get('Application\Form\Lpa\TrustCorporationForm');
            $template = 'application/authenticated/lpa/replacement-attorney/trust-form.twig';
        }

        $form->setAttribute(
            'action',
            $this->urlHelper->generate(
                'lpa/replacement-attorney/edit',
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
                $attorney->populate($form->getModelDataFromValidatedForm());

                if (!$this->lpaApplicationService->setReplacementAttorney($lpa, $attorney, $attorney->id)) {
                    throw new RuntimeException(
                        'API client failed to update replacement attorney ' . $attorney->id . ' for id: ' . $lpa->id
                    );
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
                    'day'   => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year'  => $dob->format('Y'),
                ];
            }

            $form->bind($flattenAttorneyData);
        }

        $cancelUrl = $this->urlHelper->generate('lpa/replacement-attorney', ['lpa-id' => $lpa->id]);

        $templateParams = [
            'form'      => $form,
            'cancelUrl' => $cancelUrl,
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            $template,
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }

    private function notFound(): ResponseInterface
    {
        return new \Laminas\Diactoros\Response\HtmlResponse('', 404);
    }
}
