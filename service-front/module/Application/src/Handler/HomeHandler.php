<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HomeHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dockerTag = $this->config['version']['tag'] ?? '';

        $html = $this->renderer->render(
            'application/general/home/index.twig',
            [
                'lpaFee' => Calculator::getFullFee(),
                'dockerTag' => $dockerTag,
            ]
        );

        return new HtmlResponse($html);
    }
}
