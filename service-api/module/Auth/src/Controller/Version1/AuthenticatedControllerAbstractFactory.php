<?php

namespace Application\Controller\Version1;

use Application\Model\Service\AuthenticationService;
use Application\Model\Service\EmailUpdateService;
use Application\Model\Service\PasswordChangeService;
use Application\Model\Service\PasswordResetService;
use Application\Model\Service\UserManagementService;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class AuthenticatedControllerAbstractFactory implements AbstractFactoryInterface
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
        return class_exists($requestedName) && is_subclass_of($requestedName, AbstractAuthenticatedController::class);
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

        /** @var AuthenticationService $authenticationService */
        $authenticationService = $container->get('AuthenticationService');

        if ($requestedName == AuthenticateController::class) {
            $controller = new AuthenticateController($authenticationService);
        } elseif ($requestedName == EmailController::class) {
            /** @var EmailUpdateService $emailUpdateService */
            $emailUpdateService = $container->get('EmailUpdateService');

            $controller = new EmailController($authenticationService, $emailUpdateService);
        } elseif ($requestedName == PasswordController::class) {
            /** @var PasswordChangeService $passwordChangeService */
            $passwordChangeService = $container->get('PasswordChangeService');
            /** @var PasswordResetService $passwordResetService */
            $passwordResetService = $container->get('PasswordResetService');

            $controller = new PasswordController($authenticationService, $passwordChangeService, $passwordResetService);
        } elseif ($requestedName == UsersController::class) {
            /** @var UserManagementService $userManagementService */
            $userManagementService = $container->get('UserManagementService');

            $controller = new UsersController($authenticationService, $userManagementService);
        } else {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        return $controller;
    }
}