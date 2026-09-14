<?php

declare(strict_types=1);

namespace App\Handler\Lpa\CertificateProvider;

use App\Handler\Traits\CertificateProviderHandlerTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CertificateProviderDeleteHandler implements RequestHandlerInterface
{
    use CertificateProviderHandlerTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $certificateProvider = $lpa->document->certificateProvider;

        // TODO(LPAL-2493): Get version from POST body instead
        $ifMatchVersion = $lpa->getVersion();
        try {
            // If the certificate provider is also set as the correspondent, delete those details too
            if ($certificateProvider !== null) {
                $ifMatchVersion = $this->updateCorrespondentData($lpa, $certificateProvider, true, $ifMatchVersion);
            }

            if (!$this->lpaApplicationService->deleteCertificateProvider($lpa, $ifMatchVersion)) {
                throw new RuntimeException(
                    'API client failed to delete certificate provider for id: ' . $lpa->id
                );
            }

            return new RedirectResponse(
                $this->urlHelper->generate('lpa/certificate-provider', ['lpa-id' => $lpa->id])
            );
        } catch (ConflictException $e) {
            $this->logger->info('Conflict deleting certificate provider', ['exception' => $e]);
            throw $e;
        }
    }
}
