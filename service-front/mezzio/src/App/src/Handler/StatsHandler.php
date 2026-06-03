<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Stats\StatsService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StatsHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly StatsService $statsService,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $stats = $this->statsService->getApiStats();

        return new HtmlResponse($this->renderer->render('application/general/stats', is_array($stats) ? $stats : []));
    }
}
