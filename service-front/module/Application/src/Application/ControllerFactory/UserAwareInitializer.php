<?php
namespace Application\ControllerFactory;

use Application\Controller\UserAwareInterface;

use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UserAwareInitializer implements InitializerInterface {

    /**
     * Inject the current user into classes that implement UserAwareInterface.
     *
     * @param $instance
     * @param ServiceLocatorInterface $controllerManager
     * @return mixed
     */
    public function initialize($instance, ServiceLocatorInterface $controllerManager){

        if( $instance instanceof UserAwareInterface ){

            $auth = $controllerManager->getServiceLocator()->get('AuthenticationService');

            if ($auth->hasIdentity()) {
                $instance->setUser( $auth->getIdentity() );
            }

        } // if

    } // function

} // class
