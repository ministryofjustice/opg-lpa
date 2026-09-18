<?php

declare(strict_types=1);

namespace App\Handler\Lpa\CertificateProvider;

use App\Handler\Traits\CertificateProviderHandlerTrait;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\RequestInspectorTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CertificateProviderConfirmDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;
    use CertificateProviderHandlerTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly TemplateRendererInterface $renderer,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $isPopup = $this->isXmlHttpRequest($request);
        $certificateProvider = $lpa->document->certificateProvider;
        $conflictError = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $ifMatchVersion = (int)$postData['version'];
            try {
                // If the certificate provider is also set as the correspondent, delete those details too
                $ifMatchVersion = $this->updateCorrespondentData($lpa, $certificateProvider, true, $ifMatchVersion);

                if (!$this->lpaApplicationService->deleteCertificateProvider($lpa, $ifMatchVersion)) {
                    throw new RuntimeException(
                        'API client failed to delete certificate provider for id: ' . $lpa->id
                    );
                }

                if ($isPopup) {
                    return new JsonResponse(['success' => true]);
                }

                return new RedirectResponse(
                    $this->urlHelper->generate('lpa/certificate-provider', ['lpa-id' => $lpa->id])
                );
            } catch (ConflictException $e) {
                $conflictError = $e;
            }
        }

        $templateParams = [
            'certificateProviderName' => $certificateProvider->name,
            'certificateProviderAddress' => $certificateProvider->address,
            'isPopup' => $isPopup,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/certificate-provider',
                ['lpa-id' => $lpa->id],
            ),
            'actionUrl' => $this->urlHelper->generate(
                'lpa/certificate-provider/confirm-delete',
                ['lpa-id' => $lpa->id],
            ),
            'conflictError' => $conflictError,
        ];

        $html = $this->renderer->render(
            'application/authenticated/lpa/certificate-provider/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
