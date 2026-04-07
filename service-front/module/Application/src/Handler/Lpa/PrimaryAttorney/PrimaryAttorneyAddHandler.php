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
use Application\Model\Service\Lpa\ActorReuseDetailsService;
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

class PrimaryAttorneyAddHandler implements RequestHandlerInterface
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
        private readonly ActorReuseDetailsService $actorReuseDetailsService,
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
        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        $templateParams = [
            'isPopup' => $isPopup,
        ];

        /** @var User|null $userDetails */
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);

        $queryParams = $request->getQueryParams();
        $reuseDetailsIndexFromQuery = $queryParams['reuseDetailsIndex'] ?? null;

        $actorReuseDetails = $userDetails instanceof User
            ? $this->actorReuseDetailsService->getActorReuseDetails($userDetails, $lpa)
            : [];

        if (!$isPost) {
            if ($reuseDetailsIndexFromQuery === null) {
                $reuseCount = count($actorReuseDetails);

                if ($reuseCount === 1) {
                    $templateParams['displayReuseSessionUserLink'] = true;
                } elseif ($reuseCount > 1) {
                    $reuseDetailsUrl = $this->urlHelper->generate('lpa/reuse-details', [
                        'lpa-id' => $lpa->id,
                    ], [
                        'query' => [
                            'calling-url' => $request->getUri()->getPath(),
                            'include-trusts' => true,
                            'actor-name' => 'Attorney',
                        ],
                    ]);

                    return new RedirectResponse($reuseDetailsUrl);
                }
            }
        }

        /** @var AbstractActorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpa->id])
        );
        $form->setActorData('attorney', $this->actorReuseDetailsService->getActorsList($lpa, false));


        if (!$isPost && $reuseDetailsIndexFromQuery !== null) {
            $reuseDetails = $userDetails instanceof User
                ? $this->actorReuseDetailsService->getActorReuseDetails($userDetails, $lpa)
                : [];
            if (array_key_exists($reuseDetailsIndexFromQuery, $reuseDetails)) {
                $form->bind($reuseDetails[$reuseDetailsIndexFromQuery]['data']);
            }
        }

        if ($isPost) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $reuseDetailsIndex = $postData['reuse-details'] ?? null;
            $reuseHandled = false;

            if ($reuseDetailsIndex === '0' && count($actorReuseDetails) === 1) {
                $actorDetailsToReuse = array_pop($actorReuseDetails);
                $form->bind($actorDetailsToReuse['data']);
                $reuseHandled = true;
            }

            if (!$reuseHandled) {
                $form->setData($postData);

                if ($form->isValid()) {
                    $addOk = $this->lpaApplicationService->addPrimaryAttorney(
                        $lpa,
                        new Human($form->getModelDataFromValidatedForm())
                    );

                    if (!$addOk) {
                        throw new RuntimeException(
                            'API client failed to add a primary attorney for id: ' . $lpa->id
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
        }

        $reuseDetailsForBackButton = $userDetails instanceof User
            ? $this->actorReuseDetailsService->getActorReuseDetails($userDetails, $lpa)
            : [];
        if (count($reuseDetailsForBackButton) > 1) {
            $templateParams['backButtonUrl'] = str_replace(
                'add-trust',
                'add',
                $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpa->id])
            );
        }

        $templateParams['form'] = $form;

        if ($this->allowTrust($lpa)) {
            $templateParams['switchAttorneyTypeRoute'] = 'lpa/primary-attorney/add-trust';
        }

        // Cancel URL
        $templateParams['cancelUrl'] = $this->urlHelper->generate(
            'lpa/primary-attorney',
            ['lpa-id' => $lpa->id]
        );

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/person-form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
