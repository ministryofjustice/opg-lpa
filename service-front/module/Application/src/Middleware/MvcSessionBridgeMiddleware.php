<?php

declare(strict_types=1);

namespace Application\Middleware;

use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Bridges the already-active laminas-mvc PHP session into a Mezzio SessionInterface,
 * without attempting to start a new session or change the session ID.
 *
 * This replaces SessionMiddleware (and PhpSessionPersistence) for routes that are
 * still dispatched through the laminas-mvc stack, where the PHP session has already
 * been started before our PSR-15 pipeline runs. Using the standard SessionMiddleware
 * in this context causes a PHP warning because PhpSessionPersistence tries to change
 * the session ID on an already-active session.
 *
 * On the way in:  wraps $_SESSION in a Mezzio Session and injects it as a request attribute.
 * On the way out: writes any changes back to $_SESSION.
 */
class MvcSessionBridgeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }

        $session = new Session($_SESSION, session_id());

        $request = $request->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $response = $handler->handle($request);

        // Write changes back to the native PHP session
        $_SESSION = $session->toArray();

        return $response;
    }
}
