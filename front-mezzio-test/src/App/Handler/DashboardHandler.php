<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\View\Model\ViewModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;

class DashboardHandler implements RequestHandlerInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Do some work...
        // Render and return a response:
        return new HtmlResponse($this->renderer->render(
            'dashboard',
            [] // parameters to pass to template
        ));
    }

    /*public function getDashboardContent((): string)
    {
        // Get the LPA list summary using a query if provided

        $lpasSummary = $this->getLpaApplicationService()->getLpaSummaries(null, 1, 10);
        $lpas = $lpasSummary['applications'];



return new ViewModel([
    'lpas'                  => $lpas,
    'freeText'              => $search,
    'isSearch'              => (is_string($search) && !empty($search)),
    'user'                  => [
        'lastLogin' => $this->getIdentity()->lastLogin(),
    ],
    'trackingEnabled' => $lpasSummary['trackingEnabled'],
]);
}
        return $this->renderer->render(
            'app::dashboard',
            [] // parameters to pass to template
        );
    }
}*/
