<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReplacementAttorneyConfirmDeleteHandler implements RequestHandlerInterface
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

        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $attorneyIdx = $routeResult ? $routeResult->getMatchedParams()['idx'] ?? null : null;

        if (!array_key_exists($attorneyIdx, $lpa->document->replacementAttorneys)) {
            return new HtmlResponse('', 404);
        }

        $attorney = $lpa->document->replacementAttorneys[$attorneyIdx];
        $isTrust = ($attorney instanceof TrustCorporation);

        $templateParams = [
            'deleteRoute'    => $this->urlHelper->generate(
                'lpa/replacement-attorney/delete',
                ['lpa-id' => $lpa->id, 'idx' => $attorneyIdx]
            ),
            'attorneyName'    => $attorney->name,
            'attorneyAddress' => $attorney->address,
            'isTrust'         => $isTrust,
            'cancelUrl'       => $this->urlHelper->generate(
                'lpa/replacement-attorney',
                ['lpa-id' => $lpa->id]
            ),
        ];

        if ($this->isXmlHttpRequest($request)) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/replacement-attorney/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
