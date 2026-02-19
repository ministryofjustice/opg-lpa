<?php

declare(strict_types=1);

namespace App\Listener;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authentication middleware for Mezzio
 *
 * This is a simplified version for testing purposes.
 * In production, you would integrate with a proper authentication service.
 */
class AuthenticationListener implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // TODO: Implement actual authentication logic
        // For now, this is a placeholder that checks if user is authenticated

        // Check if user is authenticated (you can implement your own logic here)
        $session = $request->getAttribute('session');
        $isAuthenticated = $session ? $session->get('user_authenticated', false) : false;

        if (!$isAuthenticated) {
            // Redirect to login page
            return new RedirectResponse('/login');
        }

        // User is authenticated, continue to next middleware/handler
        return $handler->handle($request);
    }
}
