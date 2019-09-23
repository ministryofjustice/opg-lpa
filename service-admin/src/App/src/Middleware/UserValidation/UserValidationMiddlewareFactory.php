<?php


namespace App\Middleware\UserValidation;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class UserValidationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new UserValidationMiddleware($container->get(TemplateRendererInterface::class));
    }
}
