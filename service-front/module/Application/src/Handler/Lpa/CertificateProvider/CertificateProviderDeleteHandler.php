<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\CertificateProvider;

use Application\Handler\Traits\CertificateProviderHandlerTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CertificateProviderDeleteHandler implements RequestHandlerInterface
{
    use CertificateProviderHandlerTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $certificateProvider = $lpa->document->certificateProvider;

        // If the certificate provider is also set as the correspondent, delete those details too
        if ($certificateProvider !== null) {
            $this->updateCorrespondentData($lpa, $certificateProvider, true);
        }

        if (!$this->lpaApplicationService->deleteCertificateProvider($lpa)) {
            throw new RuntimeException(
                'API client failed to delete certificate provider for id: ' . $lpa->id
            );
        }

        return new RedirectResponse(
            $this->urlHelper->generate('lpa/certificate-provider', ['lpa-id' => $lpa->id])
        );
    }
}
