<?php

namespace Auth\Controller;

use Auth\Controller\Version1;
use Auth\Model\Service\AuthenticationService;
use Auth\Model\Service\EmailUpdateService;
use Auth\Model\Service\PasswordChangeService;
use Auth\Model\Service\PasswordResetService;
use Auth\Model\Service\RegistrationService;
use Auth\Model\Service\StatsService;
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
        Version1\EmailController::class => [
            'setEmailUpdateService' => EmailUpdateService::class,
        ],
        Version1\PasswordController::class => [
            'setPasswordChangeService' => PasswordChangeService::class,
            'setPasswordResetService' => PasswordResetService::class,
        ],
        Version1\RegistrationController::class => [
            'setRegistrationService' => RegistrationService::class,
        ],
        Version1\StatsController::class => [
            'setStatsService' => StatsService::class,
        ],
        Version1\UsersController::class => [
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
        return (class_exists($requestedName) && is_subclass_of($requestedName, Version1\AbstractController::class));
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

        if (is_subclass_of($requestedName, Version1\AbstractAuthenticatedController::class)) {
            /** @var AuthenticationService $authenticationService */
            $authenticationService = $container->get(AuthenticationService::class);
            $controller = new $requestedName($authenticationService);
        } else {
            $controller = new $requestedName();
        }

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
