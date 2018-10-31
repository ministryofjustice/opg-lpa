<?php

namespace App\Handler\Initializers;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;

/**
 * Initialize Action middleware with support for rendering.
 *
 * Class TemplatingSupportInitializer
 * @package App\Action\Initializers
 */
class TemplatingSupportInitializer implements InitializerInterface
{
    /**
     * @param ContainerInterface $container
     * @param object $instance
     */
    public function __invoke(ContainerInterface $container, $instance)
    {
        if ($instance instanceof TemplatingSupportInterface && $container->has(TemplateRendererInterface::class)) {
            $instance->setTemplateRenderer($container->get(TemplateRendererInterface::class));
        }
    }
}
