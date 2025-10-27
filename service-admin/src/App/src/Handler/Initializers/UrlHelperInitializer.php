<?php

namespace App\Handler\Initializers;

use Psr\Container\ContainerInterface;
use Mezzio\Helper\UrlHelper;
use Laminas\ServiceManager\Initializer\InitializerInterface;

/**
 * Initialize handler middleware with support for the UrlHelper.
 *
 * Class UrlHelperInitializer
 * @package App\Handler\Initializers
 */
class UrlHelperInitializer implements InitializerInterface
{
    /**
     * @param ContainerInterface $container
     * @param object $instance
     */
    public function __invoke(ContainerInterface $container, $instance)
    {
        if ($instance instanceof UrlHelperInterface && $container->has(UrlHelper::class)) {
            $instance->setUrlHelper($container->get(UrlHelper::class));
        }
    }
}
