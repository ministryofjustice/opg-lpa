<?php
namespace Application;

use RuntimeException;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Application\Controller\Version1\RestController;

use Application\Library\Authentication\Adapter;
use Application\Library\Authentication\Identity;
use Application\Library\Authentication\AuthenticationListener;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;

use Application\Model\Rest\UserConsumerInterface;
use Application\Model\Rest\LpaConsumerInterface;

use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use ZF\ApiProblem\ApiProblemResponse;

use PhlyMongo\MongoCollectionFactory;
use PhlyMongo\MongoConnectionFactory;
use PhlyMongo\MongoDbFactory;
use Application\Library\ApiProblem\ApiProblem;


class Module {

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        //$sharedEvents = $eventManager->getSharedManager();


        // Setup authentication listener...
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [ new AuthenticationListener, 'authenticate' ], 500);


        /**
         * Listen for and catch ApiProblemExceptions. Convert them to a standard ApiProblemResponse.
         * TODO - move to its own listener class.
         */
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function(MvcEvent $e){

            // Marshall an ApiProblem and view model based on the exception
            $exception = $e->getParam('exception');

            if ($exception instanceof ApiProblemExceptionInterface) {

                $problem = new ApiProblem( $exception->getCode(), $exception->getMessage() );

                $e->stopPropagation();
                $response = new ApiProblemResponse($problem);
                $e->setResponse($response);
                return $response;

            }

        }, 200); // attach


    } // function

    public function getControllerConfig(){

        /*
         * ------------------------------------------------------------------
         * Setup the REST Controller
         *
         */

        return [
            'initializers' => [
                'InitRestController' => function($controller, $sm) {
                    if ($controller instanceof RestController) {

                        $locator = $sm->getServiceLocator();

                        //--------------------------------------------------
                        // Inject the resource

                        // Get the resource name (form the URL)...
                        $resource = $locator->get('Application')->getMvcEvent()->getRouteMatch()->getParam('resource');

                        // Check if the resource exists...
                        if( !$locator->has("resource-{$resource}") ){
                            throw new ApiProblemException('Unknown resource type', 404);
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

    /*
     * ------------------------------------------------------------------
     * Setup the Service Manager
     *
     */

    public function getServiceConfig() {
        return [
            'initializers' => [
                'InjectResourceEntities' => function($object, $sm) {

                    if ($object instanceof UserConsumerInterface) {

                        $userId = $sm->get('Application')->getMvcEvent()->getRouteMatch()->getParam('userId');

                        if( !isset($userId) ){
                            throw new ApiProblemException('User identifier missing from URL', 400);
                        }

                        $resource = $sm->get("resource-users");

                        $user = $resource->fetch( $userId );

                        $object->setRouteUser( $user );

                    } // UserConsumerInterface

                    if( $object instanceof LpaConsumerInterface ){

                        $lpaId = $sm->get('Application')->getMvcEvent()->getRouteMatch()->getParam('lpaId');

                        if( !isset($lpaId) ){
                            throw new ApiProblemException('LPA identifier missing from URL', 400);
                        }

                        $resource = $sm->get("resource-applications");

                        $lpa = $resource->fetch( $lpaId );
                        
                        if ($lpa instanceof ApiProblem) {
                            throw new \Exception(
                                'Error fetching ' . get_class($resource) . 
                                ' for LPA #' . $lpaId . PHP_EOL . 
                                print_r($lpa->toArray(), true)
                            );
                        }
                        
                        $object->setLpa( $lpa->getLpa() );

                    } // LpaConsumerInterface

                }, // InjectResourceEntities
            ],
            'factories' => [

                'Zend\Authentication\AuthenticationService' => function($sm) {
                    // NonPersistent persists only for the life of the request...
                    return new AuthenticationService( new NonPersistent() );
                },

                //---------------------

                // Create an instance of the MongoClient...
                'Mongo-Default' => function ($services) {
                    $config = $services->get('config')['db']['mongo']['default'];
                    $factory = new MongoConnectionFactory(
                        'mongodb://'.implode(',', $config['hosts']), // Split the array out into comma separated values.
                        $config['options']
                    );

                    return $factory->createService($services);
                },

                // Connect the above MongoClient to a DB...
                'MongoDB-Default' => function ($services) {
                    $config = $services->get('config')['db']['mongo']['default']['options'];

                    $factory = new MongoDbFactory( $config['db'], 'Mongo-Default' );

                    return $factory->createService($services);
                },

                // Access collections within the above DB...
                'MongoDB-Default-lpa' => new MongoCollectionFactory('lpa', 'MongoDB-Default'),
                'MongoDB-Default-user' => new MongoCollectionFactory('user', 'MongoDB-Default'),
                'MongoDB-Default-stats-usage' => new MongoCollectionFactory('stats-usage', 'MongoDB-Default'),
                'MongoDB-Default-stats-who' => new MongoCollectionFactory('stats-who', 'MongoDB-Default'),

            ], // factories
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
