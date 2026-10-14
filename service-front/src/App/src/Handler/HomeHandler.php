<?php

declare(strict_types=1);

namespace App\Handler;

use App\Feature;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use DateTime;
use DateTimeZone;
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
        private readonly DateTime $now,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dockerTag = $this->config['version']['tag'] ?? '';
        $showBanner = $this->now < new DateTime('2026-11-26', new DateTimeZone('UTC')) &&
            $this->now >= new DateTime('2026-11-11', new DateTimeZone('UTC'));

        $html = $this->renderer->render(
            'application/general/home/index.twig',
            array_merge($this->getTemplateVariables($request), [
                'lpaFee' => Calculator::getFullFee(),
                'dockerTag' => $dockerTag,
                'oneLoginEnabled' => Feature::OneLogin->isEnabled(),
                'pageTitle' => 'Make a lasting power of attorney',
                'showOneLoginChangesBanner' => $showBanner,
            ])
        );

        return new HtmlResponse($html);
    }
}
