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
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ReplacementAttorneyAddHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ActorReuseDetailsService $actorReuseDetailsService,
        private readonly Metadata $metadata,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $isPopup = $this->isXmlHttpRequest($request);

        // On GET requests only, check whether reuse details are available
        $displayReuseSessionUserLink = false;
        $actorDetailsToReuse = null;

        if (strtoupper($request->getMethod()) !== RequestMethodInterface::METHOD_POST) {
            $queryParams = $request->getQueryParams();
            $reuseDetailsIndex = $queryParams['reuseDetailsIndex'] ?? null;

            if ($reuseDetailsIndex === null) {
                $reuseDetails = $this->actorReuseDetailsService->getActorReuseDetails($user, $lpa);
                $reuseCount = count($reuseDetails);

                if ($reuseCount === 1) {
                    $displayReuseSessionUserLink = true;
                } elseif ($reuseCount > 1) {
                    $reuseDetailsUrl = $this->urlHelper->generate(
                        'lpa/reuse-details',
                        ['lpa-id' => $lpa->id],
                        ['query' => [
                            'calling-url'    => $request->getUri()->getPath(),
                            'include-trusts' => true,
                            'actor-name'     => 'Replacement attorney',
                        ]]
                    );

                    return new RedirectResponse($reuseDetailsUrl);
                }
            } else {
                $reuseDetails = $this->actorReuseDetailsService->getActorReuseDetails($user, $lpa);
                if (array_key_exists($reuseDetailsIndex, $reuseDetails)) {
                    $actorDetailsToReuse = $reuseDetails[$reuseDetailsIndex]['data'];
                }
            }
        }

        /** @var \Application\Form\Lpa\AttorneyForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/replacement-attorney/add', ['lpa-id' => $lpa->id])
        );
        $form->setActorData('replacement attorney', $this->actorReuseDetailsService->getActorsList($lpa, false));

        if ($actorDetailsToReuse !== null) {
            $form->setData($actorDetailsToReuse);
        }

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            // Handle "Use my details" single-reuse option
            if (($postData['reuse-details'] ?? null) === '0') {
                $reuseDetails = $this->actorReuseDetailsService->getActorReuseDetails($user, $lpa);
                if (count($reuseDetails) === 1) {
                    $actorDetailsToReuse = array_pop($reuseDetails)['data'];
                    $form->setData($actorDetailsToReuse);

                    $templateParams = [
                        'form'      => $form,
                        'cancelUrl' => $this->urlHelper->generate('lpa/replacement-attorney', ['lpa-id' => $lpa->id]),
                        'displayReuseSessionUserLink' => $displayReuseSessionUserLink,
                    ];

                    if ($isPopup) {
                        $templateParams['isPopup'] = true;
                    }

                    if ($this->actorReuseDetailsService->allowTrust($lpa)) {
                        $templateParams['switchAttorneyTypeRoute'] = 'lpa/replacement-attorney/add-trust';
                    }

                    $html = $this->renderer->render(
                        'application/authenticated/lpa/replacement-attorney/person-form.twig',
                        array_merge($this->getTemplateVariables($request), $templateParams)
                    );

                    return new HtmlResponse($html);
                }
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $attorney = new Human($form->getModelDataFromValidatedForm());

                if (!$this->lpaApplicationService->addReplacementAttorney($lpa, $attorney)) {
                    throw new RuntimeException(
                        'API client failed to add a replacement attorney for id: ' . $lpa->id
                    );
                }

                if (!array_key_exists(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED, $lpa->metadata)) {
                    $this->metadata->setReplacementAttorneysConfirmed($lpa);
                }

                $this->replacementAttorneyCleanup->cleanUp($lpa);

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

        $cancelUrl = $this->urlHelper->generate('lpa/replacement-attorney', ['lpa-id' => $lpa->id]);

        $templateParams = [
            'form'                       => $form,
            'cancelUrl'                  => $cancelUrl,
            'displayReuseSessionUserLink' => $displayReuseSessionUserLink,
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        if ($this->actorReuseDetailsService->allowTrust($lpa)) {
            $templateParams['switchAttorneyTypeRoute'] = 'lpa/replacement-attorney/add-trust';
        }

        // Show back button if multiple reuse options are available
        $reuseDetails = $this->actorReuseDetailsService->getActorReuseDetails($user, $lpa);
        if (count($reuseDetails) > 1) {
            $callingUrl = $request->getQueryParams()['callingUrl'] ?? null;
            $backButtonUrl = $callingUrl ?? $this->urlHelper->generate(
                'lpa/replacement-attorney/add',
                ['lpa-id' => $lpa->id]
            );
            $templateParams['backButtonUrl'] = str_replace('add-trust', 'add', (string) $backButtonUrl);
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/replacement-attorney/person-form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
