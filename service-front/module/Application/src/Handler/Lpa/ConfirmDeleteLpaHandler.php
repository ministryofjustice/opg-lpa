<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Router\RouteMatch;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmDeleteLpaHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly LpaApplicationService $lpaApplicationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $queryParams = $request->getQueryParams();

        $page = $queryParams['page'] ?? null;
        $lpaId = $routeMatch?->getParam('lpa-id');

        $lpa = $this->lpaApplicationService->getApplication($lpaId);

        $templateParams = [
            'lpa'  => $lpa,
            'page' => $page,
        ];

        // Check if this is an AJAX request
        $isXhr = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if ($isXhr) {
            $templateParams['isPopup'] = true;
        }

        return new HtmlResponse(
            $this->renderer->render(
                'application/authenticated/dashboard/confirm-delete.twig',
                $templateParams
            )
        );
    }
}
