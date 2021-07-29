<?php
namespace Application\Model\Service\Session;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Exception\RuntimeException;
use Aws\DynamoDb\DynamoDbClient;

/**
 * Create the SessionManager for use throughout the LPA frontend.
 *
 * Class SessionFactory
 * @package Application\Model\Service\Session
 */
class SessionFactory implements FactoryInterface {
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');

        if (!isset($config['session'])) {
            throw new RuntimeException('Session configuration setting not found');
        }

        $config = $config['session'];

        // Apply any native PHP level settings
        if (isset($config['native_settings']) && is_array($config['native_settings'])) {
            foreach ($config['native_settings'] as $k => $v) {
                ini_set('session.' . $k, $v);
            }
        }

        //----------------------------------------
        // Set the cookie domain

        // Only if it's not a Console request.
        if(!($container->get('Request') instanceof ConsoleRequest)){
            // This is requirement of the GDS service checker

            // Get the hostname of the current request
            $hostname = $container->get('Request')->getUri()->getHost();

            // and set it as the domain cookie.
            ini_set('session.cookie_domain', $hostname);
        }

        $saveHandler = $container->get('SaveHandler');
        $sessionManager = new SessionManager($saveHandler);
        return $sessionManager;
    }
}
