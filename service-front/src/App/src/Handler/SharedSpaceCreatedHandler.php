<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SharedSpaceCreatedHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(private readonly TemplateRendererInterface $renderer)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(
            'application/authenticated/shared-space/created.twig',
            $this->getTemplateVariables($request),
        ));
    }
}
