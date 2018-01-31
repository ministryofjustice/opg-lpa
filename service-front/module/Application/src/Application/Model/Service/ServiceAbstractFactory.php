<?php

namespace Application\Model\Service;

use Application\Model\Service\AddressLookup\PostcodeInfo;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\User\Details;
use Application\Model\Service\User\PasswordReset;
use Exception;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        PostcodeInfo::class => [
            'setPostcodeInfoClient' => 'PostcodeInfoClient'
        ],
        Communication::class => [
            'setUserDetailsSession' => 'UserDetailsSession'
        ],
        Details::class => [
            'setUserDetailsSession' => 'UserDetailsSession'
        ],
        PasswordReset::class => [
            'setRegisterService' => 'Register'
        ],
    ];

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return class_exists($requestedName) && is_subclass_of($requestedName, AbstractService::class);
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     * @throws Exception
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!$this->canCreateServiceWithName($serviceLocator, $name, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        $serviceName = $requestedName;

        if (is_subclass_of($serviceName, AbstractEmailService::class)) {
            $service = new $serviceName(
                $serviceLocator->get('ApiClient'),
                $serviceLocator->get('LpaApplicationService'),
                $serviceLocator->get('AuthenticationService'),
                $serviceLocator->get('Config'),
                $serviceLocator->get('TwigEmailRenderer'),
                $serviceLocator->get('MailTransport')
            );
        } else {
            $service = new $serviceName(
                $serviceLocator->get('ApiClient'),
                $serviceLocator->get('LpaApplicationService'),
                $serviceLocator->get('AuthenticationService'),
                $serviceLocator->get('Config')
            );
        }

        //  If required load any additional services into the resource
        if (array_key_exists($serviceName, $this->additionalServices)
            && is_array($this->additionalServices[$serviceName])) {
            foreach ($this->additionalServices[$serviceName] as $setterMethod => $additionalService) {
                if (!method_exists($service, $setterMethod)) {
                    throw new Exception(sprintf(
                        'The setter method %s does not exist on the requested resource %s',
                        $setterMethod,
                        $serviceName
                    ));
                }

                $service->$setterMethod($serviceLocator->get($additionalService));
            }
        }

        return $service;
    }
}
