<?php

declare(strict_types=1);

namespace App\Handler\Lpa\PrimaryAttorney;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\PrimaryAttorneyHandlerTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PrimaryAttorneyAddTrustHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use PrimaryAttorneyHandlerTrait;

    private const SESSION_KEY_CLONE = 'clone_data';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $isPopup = $this->isXmlHttpRequest($request);

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

        $form = $this->formElementManager->get('App\Form\Lpa\TrustCorporationForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/primary-attorney/add-trust', ['lpa-id' => $lpa->id])
        );

        $queryParams = $request->getQueryParams();
        $reuseDetailsIndexFromQuery = $queryParams['reuseDetailsIndex'] ?? null;
        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if (!$isPost && $reuseDetailsIndexFromQuery !== null) {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            assert($session instanceof SessionInterface);
            $reuseDetails = $this->getSeedReuseDetails($lpa, $session);
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

        $templateParams = [
            'isPopup' => $isPopup,
            'form' => $form,
            'switchAttorneyTypeRoute' => 'lpa/primary-attorney/add',
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/primary-attorney',
                ['lpa-id' => $lpa->id]
            ),
        ];

        if (isset($queryParams['callingUrl'])) {
            $templateParams['backButtonUrl'] = str_replace(
                'add-trust',
                'add',
                $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpa->id])
            );
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/trust-form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }

    private function getSeedReuseDetails(Lpa $lpa, SessionInterface $session): array
    {
        $seedId = (string) $lpa->seed;

        if (empty($seedId)) {
            return [];
        }

        $cloneData = $session->get(self::SESSION_KEY_CLONE);
        $sessionSeedData = is_array($cloneData) ? ($cloneData[$seedId] ?? null) : null;
        $seedDetails = [];

        if ($sessionSeedData === null) {
            $seedDetails = $this->lpaApplicationService->getSeedDetails($lpa->id);
            $cloneData = is_array($cloneData) ? $cloneData : [];
            $cloneData[$seedId] = $seedDetails;
            $session->set(self::SESSION_KEY_CLONE, $cloneData);
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
