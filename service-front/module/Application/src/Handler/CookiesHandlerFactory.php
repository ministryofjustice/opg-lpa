<?php

declare(strict_types=1);

namespace Application\Handler;

use Laminas\Http\Request as HttpRequest;
use Psr\Container\ContainerInterface;
use Twig\Environment as TwigEnvironment;

class CookiesHandlerFactory
{
    public function __invoke(ContainerInterface $container): CookiesHandler
    {
        return new CookiesHandler(
            $container->get(TwigEnvironment::class),
            $container->get('FormElementManager'),
            $container->get(HttpRequest::class),
        );
    }
}
