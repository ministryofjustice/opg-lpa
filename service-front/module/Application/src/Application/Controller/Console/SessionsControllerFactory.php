<?php

namespace Application\Controller\Console;

use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\System\DynamoCronLock;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class SessionsControllerFactory implements FactoryInterface
{
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
        /** @var DynamoCronLock $dynamoCronLock */
        $dynamoCronLock = $container->get('DynamoCronLock');
        /** @var SessionManager $accountCleanupService */
        $accountCleanupService = $container->get('AccountCleanupService');

        return new SessionsController($dynamoCronLock, $accountCleanupService);
    }
}
