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
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class DonorAddHandler implements RequestHandlerInterface
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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);
        $isPopup = $this->isXmlHttpRequest($request);

        // On GET requests only, check whether reuse details are available
        $displayReuseSessionUserLink = false;
        if (strtoupper($request->getMethod()) !== RequestMethodInterface::METHOD_POST) {
            $queryParams = $request->getQueryParams();
            $reuseDetailsIndex = $queryParams['reuseDetailsIndex'] ?? null;

            // If reuseDetailsIndex is present we're returning from the reuse-details screen — never redirect back
            if ($reuseDetailsIndex === null) {
                $reuseDetails = $this->actorReuseDetailsService->getActorReuseDetails($user, $lpa);
                $reuseCount = count($reuseDetails);

                if ($reuseCount === 1) {
                    // Only the session user is available — show the "Use my details" link
                    $displayReuseSessionUserLink = true;
                } elseif ($reuseCount > 1) {
                    // Multiple options — redirect to the reuse-details selection screen
                    $reuseDetailsUrl = $this->urlHelper->generate(
                        'lpa/reuse-details',
                        ['lpa-id' => $lpa->id],
                        ['query' => [
                            'calling-url'    => $request->getUri()->getPath(),
                            'include-trusts' => false,
                            'actor-name'     => 'Donor',
                        ]]
                    );

                    return new RedirectResponse($reuseDetailsUrl);
                }
            } else {
                // Pre-fill the form if a valid index was selected (not -1 / "none")
                $reuseDetails = $this->actorReuseDetailsService->getActorReuseDetails($user, $lpa);
                if (array_key_exists($reuseDetailsIndex, $reuseDetails)) {
                    $actorDetailsToReuse = $reuseDetails[$reuseDetailsIndex]['data'];
                }
            }
        }

        // If a donor has already been provided then redirect to the main donor screen
        if ($lpa->document->donor instanceof Donor) {
            return new RedirectResponse(
                $this->urlHelper->generate(
                    'lpa/donor',
                    ['lpa-id' => $lpa->id],
                    $flowChecker->getRouteOptions('lpa/donor')
                )
            );
        }

        /** @var \Application\Form\Lpa\DonorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\DonorForm');
        $form->setAttribute('action', $this->urlHelper->generate('lpa/donor/add', ['lpa-id' => $lpa->id]));
        $form->setActorData('donor', $this->actorReuseDetailsService->getActorsList($lpa));

        // Pre-fill the form with reuse details if returning from the reuse-details selection screen
        if (isset($actorDetailsToReuse)) {
            $form->setData($actorDetailsToReuse);
        }

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $donor = new Donor($form->getModelDataFromValidatedForm());

                if (!$this->lpaApplicationService->setDonor($lpa, $donor)) {
                    throw new RuntimeException(
                        'API client failed to save LPA donor for id: ' . $lpa->id
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
        }

        $cancelUrl = $this->urlHelper->generate('lpa/donor', ['lpa-id' => $lpa->id]);

        $templateParams = [
            'form'      => $form,
            'cancelUrl' => $cancelUrl,
            'displayReuseSessionUserLink' => $displayReuseSessionUserLink,
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/donor/form.twig',
            array_merge(
                $this->getTemplateVariables($request),
                $templateParams
            )
        );

        return new HtmlResponse($html);
    }
}
