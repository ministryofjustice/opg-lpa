<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\Console\ResponseSender\ConsoleResponseSender;
use Zend\Mvc\ResponseSender\SendResponseEvent;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Delegator factory for the SendResponseListener.
 *
 * Injects the ConsoleResponseSender as an event listener on the SendResponseListener
 * prior to returning it.
 */
class ConsoleResponseSenderDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @param null|array $options
     * @return \Zend\Mvc\SendResponseListener
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $sendResponseListener = $callback();
        $events = $sendResponseListener->getEventManager();
        $events->attach(SendResponseEvent::EVENT_SEND_RESPONSE, new ConsoleResponseSender(), -2000);
        return $sendResponseListener;
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
     * @return \Zend\Mvc\SendResponseListener
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }
}
