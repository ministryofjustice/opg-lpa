<?php

declare(strict_types=1);

namespace App\Middleware;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class LpaLoaderMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): LpaLoaderMiddleware
    {
        return new LpaLoaderMiddleware(
            $container->get(LpaApplicationService::class),
            $container->get(UrlHelper::class),
        );
    }
}
