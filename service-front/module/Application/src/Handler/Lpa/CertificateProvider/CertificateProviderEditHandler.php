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
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CertificateProviderEditHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;
    use CertificateProviderHandlerTrait;

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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $isPopup = $this->isXmlHttpRequest($request);

        /** @var AbstractActorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/certificate-provider/edit', ['lpa-id' => $lpa->id])
        );
        $form->setActorData('certificate provider', $this->getActorsList($lpa));

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $certificateProvider = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->lpaApplicationService->setCertificateProvider($lpa, $certificateProvider)) {
                    throw new RuntimeException(
                        'API client failed to update certificate provider for id: ' . $lpa->id
                    );
                }

                // Update correspondent data if the certificate provider is also the correspondent
                $this->updateCorrespondentData($lpa, $certificateProvider);

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
            if ($lpa->document->certificateProvider !== null) {
                $cp = $lpa->document->certificateProvider->flatten();
                $form->bind($cp);
            }
        }

        $templateParams = [
            'form' => $form,
            'isPopup' => $isPopup,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/certificate-provider',
                ['lpa-id' => $lpa->id]
            ),
        ];

        $html = $this->renderer->render(
            'application/authenticated/lpa/certificate-provider/form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
