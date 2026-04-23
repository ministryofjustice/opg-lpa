<?php

declare(strict_types=1);

namespace Application\Middleware\Factory;

use Application\Middleware\TermsAndConditionsMiddleware;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class TermsAndConditionsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TermsAndConditionsMiddleware
    {
        return new TermsAndConditionsMiddleware(
            $container->get('config'),
            $container->get(SessionUtility::class),
            $container->get(AuthenticationService::class),
            $container->get(UrlHelper::class),
        );
    }
}
