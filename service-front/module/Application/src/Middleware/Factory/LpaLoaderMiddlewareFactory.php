<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Helper\MvcUrlHelper;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Psr\Container\ContainerInterface;

/**
 * TODO(mezzio): LpaLoaderMiddleware is currently typed to MvcUrlHelper which wraps
 * Laminas MVC's RouteStackInterface. Before this factory can be used in a pure Mezzio
 * context, LpaLoaderMiddleware must be updated to accept Mezzio\Helper\UrlHelper instead.
 * At that point, replace the MvcUrlHelper injection below with:
 *     $container->get(\Mezzio\Helper\UrlHelper::class)
 */
class LpaLoaderMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LpaLoaderMiddleware
    {
        return new LpaLoaderMiddleware(
            $container->get(LpaApplicationService::class),
            $container->get(MvcUrlHelper::class),
        );
    }
}
