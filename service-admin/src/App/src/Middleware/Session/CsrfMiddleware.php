<?php

namespace App\Middleware\Session;

use App\RequestAttributes;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CsrfMiddleware
 * @package App\Middleware\Session
 */
class CsrfMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        if (!$session->has('csrf')) {
            $session->set('csrf', make_token(64));
        }

        return $handler->handle(
            $request->withAttribute(RequestAttributes::CSRF_TOKEN, $session->get('csrf'))
        );
    }
}
