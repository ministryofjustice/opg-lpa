<?php

namespace Auth\Controller\Console;

use Auth\Model\Service\AccountCleanupService;
use Auth\Model\Service\System\DynamoCronLock;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class AccountCleanupControllerFactory implements FactoryInterface
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
        /** @var AccountCleanupService $accountCleanupService */
        $accountCleanupService = $container->get('AccountCleanupService');
        /** @var DynamoCronLock $dynamoCronLock */
        $dynamoCronLock = $container->get('DynamoCronLock');

        return new AccountCleanupController($accountCleanupService, $dynamoCronLock, $container->get('config'));
    }
}