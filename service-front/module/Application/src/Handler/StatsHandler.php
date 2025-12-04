<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\Stats\Stats as StatsService;
use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class StatsHandler extends AbstractHandler implements RequestHandlerInterface
{
    public function __construct(
        private StatsService $statsService,
        TemplateRendererInterface $renderer,
    ) {
        parent::__construct($renderer);
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $stats = $this->statsService->getApiStats();

        return new HtmlResponse($this->renderer->render('application::general::stats'));
    }

    public function setStatsService(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }
}
