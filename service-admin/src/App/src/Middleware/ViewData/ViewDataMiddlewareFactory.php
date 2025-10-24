<?php

namespace App\Middleware\ViewData;

use Psr\Container\ContainerInterface;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class ViewDataMiddlewareFactory
 * @package App\Middleware\ViewData
 */
class ViewDataMiddlewareFactory
{
    /**
     * @param ContainerInterface $container
     * @return ViewDataMiddleware
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        return new ViewDataMiddleware($container->get(TemplateRendererInterface::class), $config['version']['tag']);
    }
}
