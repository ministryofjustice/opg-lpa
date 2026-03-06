<?php

declare(strict_types=1);

namespace App\Handler;

use Application\Model\Service\Lpa\Application;
use Laminas\View\Model\ViewModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;

class DashboardHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        protected Application $lpaApplicationService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Render and return a response:
        //$lpasSummary = $this->lpaApplicationService->getLpaSummaries(null, 1, 10);
        //$lpas = $lpasSummary['applications'];

        return new HtmlResponse($this->renderer->render(
            'app::dashboard',
            [] // parameters to pass to template
        ));
    }
}
