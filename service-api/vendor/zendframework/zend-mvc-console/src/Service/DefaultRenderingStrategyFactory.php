<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\Console\View\DefaultRenderingStrategy;
use Zend\Mvc\Console\View\Renderer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DefaultRenderingStrategyFactory implements FactoryInterface
{
    /**
     * Create and return DefaultRenderingStrategy (v3)
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return DefaultRenderingStrategy
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new DefaultRenderingStrategy($container->get(Renderer::class));
    }

    /**
     * Create and return DefaultRenderingStrategy (v2)
     *
     * @param ServiceLocatorInterface $container
     * @param null|string $name
     * @param null|string $requestedName
     * @return DefaultRenderingStrategy
     */
    public function createService(ServiceLocatorInterface $container, $name = null, $requestedName = null)
    {
        $requestedName = $requestedName ?: Renderer::class;
        return $this($container, $requestedName);
    }
}
