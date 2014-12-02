<?php
namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Application\Library\View\Model\JsonModel;
use Application\Controller\Version1\RestController;

use Application\Library\Authentication\Adapter;
use Application\Library\Authentication\Identity;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;

use Application\Model\Rest\ResourceInterface;

use PhlyMongo\MongoCollectionFactory;
use PhlyMongo\MongoConnectionFactory;
use PhlyMongo\MongoDbFactory;


class Module {

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $sharedEvents = $eventManager->getSharedManager();

        /*
         * ------------------------------------------------------------------
         * Perform authentication
         *
         */

        // TODO - Move this into a proper listener
        $eventManager->attach(MvcEvent::EVENT_ROUTE, function(MvcEvent $e){

            $auth = $e->getApplication()->getServiceManager()->get('AuthenticationService');

            /*
             * Do some authentication. Initially this will will just be via the token passed from front-2.
             * This token will have come from Auth-1. As this will be replaced we'll use a custom header value of:
             *      X-AuthOne
             *
             * This will leave the standard 'Authorization' namespace free for when OAuth is done properly.
             */
            $token = $e->getRequest()->getHeader('X-AuthOne');

            if (!$token) {

                // No token; set Guest....
                $auth->getStorage()->write( new Identity\Guest() );

            } else {

                $token = trim($token->getFieldValue());

                $authAdapter = new Adapter\LpaAuthOne( $token );

                $result = $auth->authenticate($authAdapter);

                # TODO - This!!!
                $auth->getStorage()->write( new Identity\User() );

            }

        }, 500); // attach

        /**
         * If a controller returns an array, by default put it into a JsonModel.
         * Needs to run before -80.
         */
        $sharedEvents->attach( 'Zend\Stdlib\DispatchableInterface', MvcEvent::EVENT_DISPATCH, function(MvcEvent $e){

            $response = $e->getResult();

            if( is_array($response) ){
                $e->setResult( new JsonModel($response) );
            }

        }, -20); // attach

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

    public function getServiceConfig() {
        return [
            'initializers' => [
                'InjectRouteIdentity' => function($object, $sm) {
                    if ($object instanceof ResourceInterface) {

                        $userId = $sm->get('Application')->getMvcEvent()->getRouteMatch()->getParam('userId');

                        $object->setRouteUser( $userId );

                    }
                },
            ],
            'factories' => [

                'Zend\Authentication\AuthenticationService' => function($sm) {
                    // NonPersistent persists only for the life of the request...
                    return new AuthenticationService( new NonPersistent() );
                },

                'Mongo-Default' => function ($services) {
                    $config = $services->get('config')['db']['mongo']['default'];
                    $factory = new MongoConnectionFactory(
                        'mongodb://'.implode(',', $config['hosts']), // Split the array out into comma separated values.
                        $config['options']
                    );

                    return $factory->createService($services);
                },
                'MongoDB-Default' => function ($services) {
                    $config = $services->get('config')['db']['mongo']['default']['options'];

                    $factory = new MongoDbFactory( $config['db'], 'Mongo-Default' );

                    return $factory->createService($services);
                },
                'MongoDB-Default-lpa' => new MongoCollectionFactory('lpa', 'MongoDB-Default'),
                'MongoDB-Default-stats-usage' => new MongoCollectionFactory('stats-usage', 'MongoDB-Default'),
                'MongoDB-Default-stats-who' => new MongoCollectionFactory('stats-who', 'MongoDB-Default'),

            ],
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
