<?php

declare(strict_types=1);

namespace Application\Helper;

use Laminas\Router\RouteStackInterface;

/**
 * URL helper for use in PSR-15 request handlers running inside the Laminas MVC context.
 * Wraps the MVC RouteStackInterface to provide a generate() method compatible with
 * Mezzio\Helper\UrlHelper, allowing handlers to generate URLs from named MVC routes
 * without requiring Mezzio\Router\RouterInterface.
 */
class MvcUrlHelper
{
    public function __construct(private readonly RouteStackInterface $router)
    {
    }

    /**
     * Generate a URL for the given route name.
     *
     * @param string $routeName  The named MVC route
     * @param array  $params     Route parameters (e.g. ['lpa-id' => 123])
     * @param array  $options    Additional router options (e.g. ['query' => [...]])
     */
    public function generate(string $routeName, array $params = [], array $options = []): string
    {
        $options['name'] = $routeName;
        return $this->router->assemble($params, $options);
    }
}
