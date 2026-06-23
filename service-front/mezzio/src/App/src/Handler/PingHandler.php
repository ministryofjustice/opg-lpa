<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\System\StatusService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PingHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly StatusService $statusService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $status = $this->statusService->check();

        $html = $this->renderer->render('application/general/ping/index.twig', [
            'status' => $status,
        ]);

        return new HtmlResponse($html);
    }
}
