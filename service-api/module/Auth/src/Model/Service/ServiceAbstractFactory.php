<?php

namespace Application\Model\Service;

use Application\Model\Service\DataAccess\LogDataSourceInterface;
use Application\Model\Service\DataAccess\UserDataSourceInterface;
use Aws\Sns\SnsClient;
use GuzzleHttp\Client as GuzzleClient;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use RuntimeException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName) && is_subclass_of($requestedName, AbstractService::class);
    }

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
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        if ($requestedName == AccountCleanupService::class) {
            /** @var UserManagementService $userManagementService */
            $userManagementService = $container->get('UserManagementService');
            /** @var SnsClient $snsClient */
            $snsClient = $container->get('SnsClient');
            /** @var GuzzleClient $guzzleClient */
            $guzzleClient = $container->get('GuzzleClient');

            $service = new AccountCleanupService(
                $this->getUserDataSource($container),
                $this->getLogDataSource($container),
                $userManagementService,
                $snsClient,
                $guzzleClient,
                $container->get('config')
            );
        } elseif ($requestedName == PasswordChangeService::class) {
            /** @var AuthenticationService $authenticationService */
            $authenticationService = $container->get('AuthenticationService');

            $service = new PasswordChangeService(
                $this->getUserDataSource($container),
                $this->getLogDataSource($container),
                $authenticationService
            );
        } else {
            $service = new $requestedName(
                $this->getUserDataSource($container),
                $this->getLogDataSource($container)
            );
        }

        return $service;
    }

    /**
     * Returns an data source that implement UserInterface from the service manager.
     *
     * @param ContainerInterface $container
     * @return UserDataSourceInterface
     */
    private function getUserDataSource(ContainerInterface $container)
    {
        if (!$container->has('UserDataSource')) {
            throw new RunTimeException('UserDataSource has not been defined in the service manager');
        }

        $access = $container->get('UserDataSource');

        if (!($access instanceof UserDataSourceInterface)) {
            throw new RunTimeException('UserDataSource must implement UserDataSourceInterface');
        }

        return $access;
    }

    /**
     * Returns an data source that implement LogDataSourceInterface from the service manager.
     *
     * @param ContainerInterface $container
     * @return LogDataSourceInterface
     */
    private function getLogDataSource(ContainerInterface $container)
    {
        if (!$container->has('LogDataSource')) {
            throw new RunTimeException('LogDataSource has not been defined in the service manager');
        }

        $access = $container->get('LogDataSource');

        if (!($access instanceof LogDataSourceInterface)) {
            throw new RunTimeException('LogDataSource must implement LogDataSourceInterface');
        }

        return $access;
    }
}
