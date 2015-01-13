<?php
namespace Application;

use RuntimeException;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Application\Library\View\Model\JsonModel;
use Application\Controller\Version1\RestController;

use Application\Library\Authentication\Adapter;
use Application\Library\Authentication\Identity;
use Application\Library\Authentication\AuthenticationListener;

use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;

use Application\Model\Rest\UserConsumerInterface;
use Application\Model\Rest\LpaConsumerInterface;

use PhlyMongo\MongoCollectionFactory;
use PhlyMongo\MongoConnectionFactory;
use PhlyMongo\MongoDbFactory;


class Module {

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $sharedEvents = $eventManager->getSharedManager();


        // Setup authentication listener...
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [ new AuthenticationListener, 'authenticate' ], 500);


        /**
         * If a controller returns an array, by default put it into a JsonModel.
         * Needs to run before -80.
         */
        $sharedEvents->attach( 'Zend\Stdlib\DispatchableInterface', MvcEvent::EVENT_DISPATCH, function(MvcEvent $e){

            $response = $e->getResult();

            if( is_array($response) ){
                throw new \RuntimeException('Deprecated - this should never be called.');
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
                            // TODO
                            throw new RuntimeException('Unknown resource type');
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
                'InjectResourceEntities' => function($object, $sm) {

                    if ($object instanceof UserConsumerInterface) {

                        $userId = $sm->get('Application')->getMvcEvent()->getRouteMatch()->getParam('userId');

                        if( !isset($userId) ){
                            throw new RuntimeException('No user ID in URL.');
                        }

                        $resource = $sm->get("resource-users");

                        $user = $resource->fetch( $userId );

                        $object->setRouteUser( $user );

                    } // UserConsumerInterface

                    if( $object instanceof LpaConsumerInterface ){

                        $lpaId = $sm->get('Application')->getMvcEvent()->getRouteMatch()->getParam('lpaId');

                        if( !isset($lpaId) ){
                            throw new RuntimeException('No LPA ID in URL.');
                        }

                        $resource = $sm->get("resource-applications");

                        $lpa = $resource->fetch( $lpaId );

                        $object->setLpa( $lpa->getLpa() );

                    } // LpaConsumerInterface

                }, // InjectResourceEntities
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
                'MongoDB-Default-user' => new MongoCollectionFactory('user', 'MongoDB-Default'),
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
