<?php

namespace Application;

use Application\Controller\Version1\RestController;
use Application\DataAccess\Mongo\CollectionFactory;
use Application\DataAccess\Mongo\DatabaseFactory;
use Application\DataAccess\Mongo\ManagerFactory;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use Application\Library\Authentication\AuthenticationListener;
use Application\Model\Service\System\DynamoCronLock;
use Aws\DynamoDb\DynamoDbClient;
use DynamoQueue\Queue\Client as DynamoQueue;
use Opg\Lpa\Logger\Logger;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblemResponse;

class Module {

    const VERSION = '3.0.3-dev';

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //---

        $request = $e->getApplication()->getServiceManager()->get('Request');

        if( !($request instanceof \Zend\Console\Request) ) {

            // Setup authentication listener...
            $eventManager->attach(MvcEvent::EVENT_ROUTE, [new AuthenticationListener, 'authenticate'], 500);

            // Register error handler for dispatch and render errors
            $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleError'));
            $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'handleError'));

        }

    } // function

    public function getControllerConfig(){

        /*
         * ------------------------------------------------------------------
         * Setup the REST Controller
         *
         */

        return [
            'initializers' => [
                'InitRestController' => function($sm, $controller) {
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
            'factories' => [
                'Zend\Authentication\AuthenticationService' => function ($sm) {
                    // NonPersistent persists only for the life of the request...
                    return new AuthenticationService(new NonPersistent());
                },
                'DynamoCronLock' => function ($sm) {
                    $config = $sm->get('config')['cron']['lock']['dynamodb'];

                    $config['keyPrefix'] = $sm->get('config')['stack']['name'];

                    return new DynamoCronLock($config);
                },

                // Create an instance of the MongoClient...
                ManagerFactory::class => ManagerFactory::class,

                // Connect the above MongoClient to a DB...
                DatabaseFactory::class => DatabaseFactory::class,

                // Access collections within the above DB...
                CollectionFactory::class . '-lpa' => new CollectionFactory('lpa'),
                CollectionFactory::class . '-user' => new CollectionFactory('user'),
                CollectionFactory::class . '-stats-who' => new CollectionFactory('whoAreYou'),
                CollectionFactory::class . '-stats-lpas' => new CollectionFactory('lpaStats'),

                // Get Dynamo Queue Client
                'DynamoQueueClient' => function ($sm) {
                    $config = $sm->get('config')['pdf']['DynamoQueue'];

                    $dynamoDb = new DynamoDbClient($config['client']);

                    return new DynamoQueue($dynamoDb, $config['settings']);
                },
            ], // factories
        ];
    } // function

    public function getConfig(){
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Use our logger to send this exception to its various destinations
     * Listen for and catch ApiProblemExceptions. Convert them to a standard ApiProblemResponse.
     *
     * @param MvcEvent $e
     */
    public function handleError(MvcEvent $e)
    {
        // Marshall an ApiProblem and view model based on the exception
        $exception = $e->getParam('exception');

        if ($exception instanceof ApiProblemExceptionInterface) {

            $problem = new ApiProblem( $exception->getCode(), $exception->getMessage() );

            $e->stopPropagation();
            $response = new ApiProblemResponse($problem);
            $e->setResponse($response);

            $logger = Logger::getInstance();
            $logger->err($exception->getMessage());

            return $response;

        }
    }

} // class
