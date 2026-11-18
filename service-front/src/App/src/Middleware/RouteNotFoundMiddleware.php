<?php

declare(strict_types=1);

namespace App\Middleware;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteNotFoundMiddleware implements MiddlewareInterface
{
    public const string TEMPLATE_404 = 'error::404';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
    ) {
    }
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);

        if ($routeResult->isFailure() && !$routeResult->isMethodFailure()) {
            return new HtmlResponse(
                $this->renderer->render(self::TEMPLATE_404),
                404
            );
        } else {
            return $handler->handle($request);
        }
    }
}
