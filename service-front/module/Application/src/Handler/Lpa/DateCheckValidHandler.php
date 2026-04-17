<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DateCheckValidHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $returnRoute = $queryParams['return-route'] ?? 'user/dashboard';

        $html = $this->renderer->render(
            'application/authenticated/lpa/date-check/valid.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'returnRoute' => $returnRoute,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
