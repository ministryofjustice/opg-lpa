<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\CookiesHandler;
use Laminas\Http\Request as HttpRequest;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class CookiesHandlerFactory
{
    public function __invoke(ContainerInterface $container): CookiesHandler
    {
        return new CookiesHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get('FormElementManager'),
            $container->get(HttpRequest::class),
        );
    }
}
