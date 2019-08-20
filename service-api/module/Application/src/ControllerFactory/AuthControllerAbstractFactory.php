<?php

namespace Application\ControllerFactory;

use Application\Controller\Version2\Auth as AuthControllers;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class AuthControllerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @var array
     */
    private $serviceMappings = [
        AuthControllers\EmailController::class     => Service\Email\Service::class,
        AuthControllers\PasswordController::class  => Service\Password\Service::class,
        AuthControllers\UsersController::class     => Service\Users\Service::class,
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return (class_exists($requestedName) && is_subclass_of($requestedName, AuthControllers\AbstractAuthController::class));
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed
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

        //  Create the controller injecting the appropriate services
        /** @var AuthenticationService $authenticationService */
        $authenticationService = $container->get(AuthenticationService::class);
        $service = null;

        if (isset($this->serviceMappings[$requestedName])) {
            $service = $container->get($this->serviceMappings[$requestedName]);
        }

        $controller = new $requestedName($authenticationService, $service);

        return $controller;
    }
}
