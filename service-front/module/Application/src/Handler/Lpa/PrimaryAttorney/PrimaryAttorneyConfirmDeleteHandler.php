<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PrimaryAttorney;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PrimaryAttorneyConfirmDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $isPopup = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        // Get the attorney index from the route params
        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $attorneyIdx = $params['idx'] ?? null;

        if ($attorneyIdx === null || !array_key_exists($attorneyIdx, $lpa->document->primaryAttorneys)) {
            return new HtmlResponse('Page not found', 404);
        }

        $attorney = $lpa->document->primaryAttorneys[$attorneyIdx];

        // Setting the trust flag
        $isTrust = isset($attorney->number);

        $templateParams = [
            'deleteRoute' => $this->urlHelper->generate(
                'lpa/primary-attorney/delete',
                ['lpa-id' => $lpa->id, 'idx' => $attorneyIdx]
            ),
            'attorneyName' => $attorney->name,
            'attorneyAddress' => $attorney->address,
            'isTrust' => $isTrust,
            'isPopup' => $isPopup,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/primary-attorney',
                ['lpa-id' => $lpa->id]
            ),
        ];

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
