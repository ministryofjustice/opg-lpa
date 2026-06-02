<?php

declare(strict_types=1);

namespace App\Handler\Lpa\CertificateProvider;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use App\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CertificateProviderConfirmDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $isPopup = $this->isXmlHttpRequest($request);

        $certificateProvider = $lpa->document->certificateProvider;

        $templateParams = [
            'deleteRoute' => $this->urlHelper->generate(
                'lpa/certificate-provider/delete',
                ['lpa-id' => $lpa->id]
            ),
            'certificateProviderName' => $certificateProvider !== null ? $certificateProvider->name : null,
            'certificateProviderAddress' => $certificateProvider !== null ? $certificateProvider->address : null,
            'isPopup' => $isPopup,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/certificate-provider',
                ['lpa-id' => $lpa->id]
            ),
        ];

        $html = $this->renderer->render(
            'application/authenticated/lpa/certificate-provider/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
