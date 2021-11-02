<?php

namespace App\Middleware\ViewData;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Plates\PlatesRenderer;

/**
 * Class ViewDataMiddleware
 * @package App\Middleware\ViewData
 */
class ViewDataMiddleware implements MiddlewareInterface
{
    /**
     * @var PlatesRenderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $dockerTag;

    /**
     * @param PlatesRenderer $renderer
     * @param string $dockerTag
     */
    public function __construct(PlatesRenderer $renderer, $dockerTag)
    {
        $this->renderer = $renderer;
        $this->dockerTag = $dockerTag;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $this->renderer->addDefaultParam(PlatesRenderer::TEMPLATE_ALL, 'dockerTag', $this->dockerTag);
        $this->renderer->addDefaultParam(PlatesRenderer::TEMPLATE_ALL, 'user', $user);

        return $handler->handle($request);
    }
}
