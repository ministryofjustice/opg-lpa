<?php
namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Application\Library\View\Model\JsonModel;
use Application\Controller\Version1\RestController;

class Module
{
    public function onBootstrap(MvcEvent $e){
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $sharedEvents = $eventManager->getSharedManager();

        /**
         * If a controller returns an array, by default put it into a JsonModel.
         * Needs to run before -80.
         */
        $sharedEvents->attach( 'Zend\Stdlib\DispatchableInterface', MvcEvent::EVENT_DISPATCH, function(MvcEvent $e){

            $response = $e->getResult();

            if( is_array($response) ){
                $e->setResult( new JsonModel($response) );
            }

        }, -20);

    } // function

    public function getControllerConfig(){

        return [
            'initializers' => [
                'InitRestController' => function($controller, $sm) {
                    if ($controller instanceof RestController) {

                        $locator = $sm->getServiceLocator();

                        // Get the resource name (form the URL)...
                        $resource = $locator->get('Application')->getMvcEvent()->getRouteMatch()->getParam('resource');

                        // Check if the resource exists...
                        if( !$locator->has("resource-{$resource}") ){
                            // Error
                            die("Doesn't exist");
                        }

                        // Get the resource...
                        $resource = $locator->get("resource-{$resource}");

                        // Inject it into the Controller...
                        $controller->setResource( $resource );

                    }
                }, // InitRestController
            ], // initializers
        ];

    } // function

    public function getConfig(){
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig(){
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    } // function

} // class
