<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Router;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Delegator factory for the Router service.
 *
 * If a console environment is detected, returns the ConsoleRouter service
 * instead of the default router.
 */
class ConsoleRouterDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * Known router names/aliases; allows auto-selection of console router.
     *
     * @var string[]
     */
    private $knownRouterNames = [
        'router',
        'zend\\router\routestackinterface',
    ];

    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @param null|array $options
     * @return \Zend\Mvc\Router\RouteStackInterface
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        // Console environment?
        if ($name === 'ConsoleRouter'                                      // force console router
            || (in_array(strtolower($name), $this->knownRouterNames, true)
                && Console::isConsole())                                   // auto detect console
        ) {
            return $container->get('ConsoleRouter');
        }

        return $callback();
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
     * @return \Zend\Mvc\Router\RouteStackInterface
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }
}
