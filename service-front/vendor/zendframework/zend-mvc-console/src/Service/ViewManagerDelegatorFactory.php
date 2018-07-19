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

class ViewManagerDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * Return a ConsoleViewManager if in a Console environment.
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @param null|array $options
     * @return \Zend\Mvc\Console\View\ViewManager|Zend\Mvc\View\Http\ViewManager
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        if (! Console::isConsole() || ! $container->has('ConsoleViewManager')) {
            return $callback();
        }

        return $container->get('ConsoleViewManager');
    }

    /**
     * Return a ConsoleViewManager if in a Console environment. (v2)
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @param callable $callback
     * @return \Zend\Mvc\Console\View\ViewManager|Zend\Mvc\View\Http\ViewManager
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }
}
