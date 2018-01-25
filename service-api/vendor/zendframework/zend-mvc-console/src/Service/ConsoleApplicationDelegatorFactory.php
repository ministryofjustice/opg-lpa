<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Service;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Delegator factory for the Application instance.
 *
 * If in a console environment, attaches the console view manager as an event
 * listener on the Application prior to returning it.
 *
 * @deprecated since 1.1.8 Use the ViewManagerDelegatorFactory instead.
 */
class ConsoleApplicationDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @param null|array $options
     * @return \Zend\Mvc\ApplicationInterface
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $application = $callback();

        if (! Console::isConsole()) {
            return $application;
        }

        $container->get('ConsoleViewManager')->attach($application->getEventManager());
        return $application;
    }

    /**
     * zend-servicemanager v2 compatibility.
     *
     * Proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @param callable $callback
     * @return \Zend\Mvc\ApplicationInterface
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }
}
