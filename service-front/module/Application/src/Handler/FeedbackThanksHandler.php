<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FeedbackThanksHandler implements RequestHandlerInterface
{
    private TemplateRendererInterface $renderer;
    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams   = $request->getQueryParams();
        $returnTarget  = $queryParams['returnTarget'] ?? '';

        $returnTarget = urldecode((string) $returnTarget);

        if (empty($returnTarget)) {
            $returnTarget = '/';
        }

        $html = $this->renderer->render(
            'application/general/feedback/thanks.twig',
            [
                'returnTarget' => $returnTarget,
            ]
        );

        return new HtmlResponse($html);
    }
}
