<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\Download;

use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class DownloadFileHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $pdfType = $params['pdf-type'] ?? '';

        if (!$this->pdfIsReady($lpa->getId(), $pdfType)) {
            return new RedirectResponse(
                $this->urlHelper->generate('lpa/download', [
                    'lpa-id'   => $lpa->getId(),
                    'pdf-type' => $pdfType,
                ])
            );
        }

        $fileContents = $this->lpaApplicationService->getPdfContents($lpa->getId(), $pdfType);

        if (!is_string($fileContents) || $fileContents === '') {
            return new RedirectResponse(
                $this->urlHelper->generate('lpa/download', [
                    'lpa-id'   => $lpa->getId(),
                    'pdf-type' => $pdfType,
                ])
            );
        }

        $fileName = $this->getFilename($lpa, $pdfType);

        $userAgent = $request->getHeaderLine('User-Agent');
        $disposition = (stripos($userAgent, 'edge/') !== false)
            ? 'attachment; filename="' . $fileName . '"'
            : 'inline; filename="' . $fileName . '"';

        $response = new Response();
        $response->getBody()->write($fileContents);

        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Content-Length', (string) strlen($fileContents))
            ->withHeader('Content-Disposition', $disposition);
    }

    private function pdfIsReady(int $lpaId, string $pdfType): bool
    {
        $result = $this->lpaApplicationService->getPdf($lpaId, $pdfType);

        $status = is_array($result) ? ($result['status'] ?? 'unknown') : 'unknown';

        $this->logger->debug('PDF status is ' . $status, [
            'lpaId' => $lpaId,
            'pdfType' => $pdfType,
        ]);

        if (!is_array($result)) {
            return (bool) $result;
        }

        return ($result['status'] === 'ready');
    }

    private function getFilename(Lpa $lpa, string $pdfType): string
    {
        $lpaTypeChar = '';

        if ($pdfType === 'lp1') {
            $lpaTypeChar = ($lpa->document->type === Document::LPA_TYPE_PF ? 'F' : 'H');
        }

        $draftString = '';

        if (!$lpa->isStateCompleted()) {
            $draftString = 'Draft-';
        }

        return $draftString . 'Lasting-Power-of-Attorney-' . strtoupper($pdfType) . $lpaTypeChar . '.pdf';
    }
}
