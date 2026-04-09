<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\CertificateProvider;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Traits\CertificateProviderHandlerTrait;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CertificateProviderAddHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;
    use CertificateProviderHandlerTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly Metadata $metadata,
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

        /** @var User $userDetails */
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);

        $queryParams = $request->getQueryParams();
        $reuseDetailsIndexFromQuery = $queryParams['reuseDetailsIndex'] ?? null;

        $actorReuseDetails = $this->actorReuseDetailsService->getActorReuseDetails(
            $userDetails,
            $lpa,
            false
        );

        $templateParams = [
            'isPopup' => $isPopup,
        ];

        // If a certificate provider already exists, redirect to index
        if ($lpa->document->certificateProvider instanceof CertificateProvider) {
            $route = 'lpa/certificate-provider';

            return new RedirectResponse(
                $this->urlHelper->generate(
                    $route,
                    ['lpa-id' => $lpa->id],
                    $flowChecker->getRouteOptions($route)
                )
            );
        }

        // On GET, check reuse options (redirect to reuse-details if > 1 option available)
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
                            'include-trusts' => false,
                            'actor-name' => 'Certificate provider',
                        ],
                    ]);

                    return new RedirectResponse($reuseDetailsUrl);
                }
            }
        }

        /** @var AbstractActorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/certificate-provider/add', ['lpa-id' => $lpa->id])
        );
        $form->setActorData('certificate provider', $this->getActorsList($lpa));

        // On GET with reuseDetailsIndex, bind the selected actor details
        if (!$isPost && $reuseDetailsIndexFromQuery !== null) {
            if (array_key_exists($reuseDetailsIndexFromQuery, $actorReuseDetails)) {
                $form->bind($actorReuseDetails[$reuseDetailsIndexFromQuery]['data']);
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
                    $setOk = $this->lpaApplicationService->setCertificateProvider(
                        $lpa,
                        new CertificateProvider($form->getModelDataFromValidatedForm())
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to save certificate provider for id: ' . $lpa->id
                        );
                    }

                    // Remove the skipped metadata tag if it was set
                    $this->metadata->removeMetadata($lpa, Lpa::CERTIFICATE_PROVIDER_SKIPPED);

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

        // Add reuse details back button if multiple reuse options exist
        if (count($this->actorReuseDetailsService->getActorReuseDetails($userDetails, $lpa, false)) > 1) {
            $templateParams['backButtonUrl'] = str_replace(
                'add-trust',
                'add',
                $this->urlHelper->generate('lpa/certificate-provider/add', ['lpa-id' => $lpa->id])
            );
        }

        $templateParams['form'] = $form;
        $templateParams['cancelUrl'] = $this->urlHelper->generate(
            'lpa/certificate-provider',
            ['lpa-id' => $lpa->id]
        );

        $html = $this->renderer->render(
            'application/authenticated/lpa/certificate-provider/form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
