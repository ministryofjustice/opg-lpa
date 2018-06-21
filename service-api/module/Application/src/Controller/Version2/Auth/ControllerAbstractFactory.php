<?php

namespace Application\Controller\Version2\Auth;

use Application\Controller\Version2\Auth;
use Auth\Model\Service\AuthenticationService;
use Auth\Model\Service\EmailUpdateService;
use Auth\Model\Service\PasswordService;
use Auth\Model\Service\RegistrationService;
use Auth\Model\Service\UserManagementService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Exception;

class ControllerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        Auth\EmailController::class => [
            'setEmailUpdateService' => EmailUpdateService::class,
        ],
        Auth\PasswordController::class => [
            'setPasswordService' => PasswordService::class,
        ],
        Auth\RegistrationController::class => [
            'setRegistrationService' => RegistrationService::class,
        ],
        Auth\UsersController::class => [
            'setUserManagementService' => UserManagementService::class,
        ],
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
        return (class_exists($requestedName) && is_subclass_of($requestedName, Auth\AbstractController::class));
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf('Abstract factory %s can not create the requested service %s', get_class($this), $requestedName));
        }

        /** @var AuthenticationService $authenticationService */
        $authenticationService = $container->get(AuthenticationService::class);

        $controller = new $requestedName($authenticationService);

        //  If required load any additional services into the resource
        if (array_key_exists($requestedName, $this->additionalServices)
            && is_array($this->additionalServices[$requestedName])) {
            foreach ($this->additionalServices[$requestedName] as $setterMethod => $additionalService) {
                if (!method_exists($controller, $setterMethod)) {
                    throw new Exception(sprintf(
                        'The setter method %s does not exist on the requested resource %s',
                        $setterMethod,
                        $requestedName
                    ));
                }

                $controller->$setterMethod($container->get($additionalService));
            }
        }

        return $controller;
    }
}
