<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Guidance\Guidance;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GuidanceHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly Guidance $guidanceService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->guidanceService->parseMarkdown();

        $isAjax = $this->isAjaxRequest($request);

        $template = $isAjax
            ? 'guidance/opg-help-content.twig'
            : 'guidance/opg-help-with-layout.twig';

        $html = $this->renderer->render($template, $data);

        return new HtmlResponse($html);
    }

    private function isAjaxRequest(ServerRequestInterface $request): bool
    {
        return strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }
}
