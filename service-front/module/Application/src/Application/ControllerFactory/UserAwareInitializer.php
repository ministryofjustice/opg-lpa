<?php

namespace Application\ControllerFactory;

use Application\Controller\AbstractAuthenticatedController;
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserAwareInitializer implements InitializerInterface
{
    /**
     * Inject the current user into classes that extend AbstractAuthenticatedController
     *
     * @param $instance
     * @param ServiceLocatorInterface $controllerManager
     * @return mixed
     */
    public function initialize($instance, ServiceLocatorInterface $controllerManager)
    {
        if ($instance instanceof AbstractAuthenticatedController) {
            $auth = $controllerManager->getServiceLocator()->get('AuthenticationService');

            if ($auth->hasIdentity()) {
                $instance->setUser($auth->getIdentity());
            }
        }
    }
}
