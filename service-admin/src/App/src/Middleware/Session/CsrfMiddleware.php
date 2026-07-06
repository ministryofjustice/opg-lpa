<?php

namespace App\Middleware\Session;

use App\Handler\Traits\JwtTrait;
use Mezzio\Router\RouteResult;
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
    use JwtTrait;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Unauthenticated routes have no JWT session data — skip CSRF token generation.
        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult instanceof RouteResult) {
            $matchedRoute = $routeResult->getMatchedRoute();
            $options = $matchedRoute !== false ? $matchedRoute->getOptions() : [];
            if (!empty($options['unauthenticated_route'])) {
                return $handler->handle($request);
            }
        }

        $csrf = $this->getTokenData('csrf');

        if (is_null($csrf)) {
            //  Generate a secret csrf value before proceeding
            $secret = make_token(64);

            $this->addTokenData('csrf', $secret);
        }

        return $handler->handle($request);
    }
}
