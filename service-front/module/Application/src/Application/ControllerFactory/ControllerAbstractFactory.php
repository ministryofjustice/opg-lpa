<?php
namespace Application\ControllerFactory;

use RuntimeException;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Stdlib\DispatchableInterface as Dispatchable;

/**
 * Creates a controller based on those requested without a specific entry in the controller service locator.
 *
 * Class ControllerAbstractFactory
 * @package Application\ControllerFactory
 */
class ControllerAbstractFactory implements AbstractFactoryInterface{

    /**
     * Checks whether this abstract factory can create the requested controller.
     *
     * @param ServiceLocatorInterface $locator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName( ServiceLocatorInterface $locator, $name, $requestedName ){

        $controllerName = $this->getControllerName( $requestedName );

        return class_exists($controllerName);

    }

    /**
     * Creates the requested controller.
     *
     * @param ServiceLocatorInterface $locator
     * @param $name
     * @param $requestedName
     * @return Dispatchable
     */
    public function createServiceWithName( ServiceLocatorInterface $locator, $name, $requestedName ){

        $controllerName = $this->getControllerName( $requestedName );

        $controller = new $controllerName;

        // Ensure it's Dispatchable...
        if( !( $controller instanceof Dispatchable ) ){
            throw new RuntimeException( 'Requested controller class is not Dispatchable' );
        }

        return $controller;

    }

    //----------------------------------------

    /**
     * Appends the namespace to the requested controller.
     *
     * @param $requestedName
     * @return string
     */
    private function getControllerName( $requestedName ){

        return'Application\Controller\\' . $requestedName;

    }

} // class
