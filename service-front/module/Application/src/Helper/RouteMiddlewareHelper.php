<?php

declare(strict_types=1);

namespace Application\Helper;

use Application\Listener\AuthenticationListener;
use Application\Listener\TermsAndConditionsListener;
use Application\Listener\UserDetailsListener;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Middleware\RouteMatchMiddleware;
use Laminas\Mvc\Middleware\PipeSpec;

class RouteMiddlewareHelper
{
    /**
     * Builds a PipeSpec with the standard authenticated middleware stack, minus any classes in $ignore.
     *
     * @param string $handlerClass The handler to append at the end of the pipeline.
     * @param string[] $ignore Middleware classes to omit from the stack.
     * @param string[] $extra Additional middleware classes to insert immediately before the handler.
     */
    public static function addMiddleware(string $handlerClass, array $ignore, array $extra = []): PipeSpec
    {
        $middlewares = array_diff([
            RouteMatchMiddleware::class,
            AuthenticationListener::class,
            UserDetailsListener::class,
            TermsAndConditionsListener::class,
            LpaLoaderMiddleware::class,
        ], $ignore);

        array_push($middlewares, ...$extra);
        $middlewares[] = $handlerClass;

        return new PipeSpec(...$middlewares);
    }
}
