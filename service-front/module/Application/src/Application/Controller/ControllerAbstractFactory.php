<?php

namespace Application\Controller;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ControllerAbstractFactory implements AbstractFactoryInterface
{

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
        return (class_exists($requestedName) && is_subclass_of($requestedName, AbstractBaseController::class));
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
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

        $formElementManager = $serviceLocator->get('FormElementManager');
        $authenticationService = $serviceLocator->get('AuthenticationService');
        $config = $serviceLocator->get('Config');
        $cache = $serviceLocator->get('Cache');

        if (is_subclass_of($requestedName, AbstractAuthenticatedController::class)) {
            $sessionManager = $serviceLocator->get('SessionManager');
            $userDetailsSession = $serviceLocator->get('UserDetailsSession');
            $lpaApplicationService = $serviceLocator->get('LpaApplicationService');
            $aboutYouDetails = $serviceLocator->get('AboutYouDetails');

            if (is_subclass_of($requestedName, AbstractLpaController::class)) {
                $service = new $requestedName(
                    $formElementManager,
                    $authenticationService,
                    $config,
                    $cache,
                    $sessionManager,
                    $userDetailsSession,
                    $lpaApplicationService,
                    $aboutYouDetails,
                    $serviceLocator->get('ApplicantCleanup'),
                    $serviceLocator->get('ReplacementAttorneyCleanup')
                );
            } else {
                $service = new $requestedName(
                    $formElementManager,
                    $authenticationService,
                    $config,
                    $cache,
                    $sessionManager,
                    $userDetailsSession,
                    $lpaApplicationService,
                    $aboutYouDetails
                );
            }
        } else {
            $service = new $requestedName($formElementManager, $authenticationService, $config, $cache);
        }

        return $service;
    }
}