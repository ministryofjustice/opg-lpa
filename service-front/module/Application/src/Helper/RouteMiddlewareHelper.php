<?php

declare(strict_types=1);

namespace Application\Helper;

use Application\Middleware\AuthenticationMiddleware;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Middleware\RouteMatchMiddleware;
use Application\Middleware\TermsAndConditionsMiddleware;
use Application\Middleware\UserDetailsMiddleware;
use Laminas\Mvc\Middleware\PipeSpec;

class RouteMiddlewareHelper
{
    /**
     * Builds a PipeSpec with the standard authenticated middleware stack, minus any classes in $ignore.
     *
     * @param string $handlerClass The handler to append at the end of the pipeline.
     * @param string[] $ignore Middleware classes to omit from the stack.
     */
    public static function addMiddleware(string $handlerClass, array $ignore): PipeSpec
    {
        $middlewares = array_diff([
            RouteMatchMiddleware::class,
            AuthenticationMiddleware::class,
            UserDetailsMiddleware::class,
            TermsAndConditionsMiddleware::class,
            LpaLoaderMiddleware::class,
        ], $ignore);

        $middlewares[] = $handlerClass;

        return new PipeSpec(...$middlewares);
    }
}
