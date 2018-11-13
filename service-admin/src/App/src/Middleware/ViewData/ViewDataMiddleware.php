<?php

namespace App\Middleware\ViewData;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Plates\PlatesRenderer;

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
     * ViewDataMiddleware constructor.
     * @param PlatesRenderer $renderer
     */
    public function __construct(PlatesRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //  Extract the user details from the request
        $token = $request->getAttribute('token');

        //  TODO - Change this later
        if (!is_null($token)) {
            $user = new \ArrayObject($token);

            $this->renderer->addDefaultParam(PlatesRenderer::TEMPLATE_ALL, 'identity', $user);
        }

        return $handler->handle($request);
    }
}
