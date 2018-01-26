<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Router;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\Router\RouterConfigTrait;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConsoleRouterFactory implements FactoryInterface
{
    use RouterConfigTrait;

    /**
     * Create and return the console SimpleRouteStack.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return SimpleRouteStack
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['console']['router']) ? $config['console']['router'] : [];
        return $this->createRouter(SimpleRouteStack::class, $config, $container);
    }

    /**
     * Create and return SimpleRouteStack instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return SimpleRouteStack
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, SimpleRouteStack::class);
    }
}
