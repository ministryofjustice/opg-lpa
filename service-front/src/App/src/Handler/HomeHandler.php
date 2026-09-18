<?php

declare(strict_types=1);

namespace App\Handler;

use App\Feature;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HomeHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

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
            array_merge($this->getTemplateVariables($request), [
                'lpaFee' => Calculator::getFullFee(),
                'dockerTag' => $dockerTag,
                'oneLoginEnabled' => Feature::OneLogin->isEnabled(),
                'pageTitle' => 'Make a lasting power of attorney',
            ])
        );

        return new HtmlResponse($html);
    }
}
