<?php

namespace Application;

use Application\Model\DataAccess\Mongo;
use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use Application\Library\Authentication\AuthenticationListener;
use Application\Model\Service\System\DynamoCronLock;
use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use Interop\Container\ContainerInterface;
use Opg\Lpa\Logger\Logger;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\ApiProblem\ApiProblemResponse;

class Module
{
    const VERSION = '3.0.3-dev';

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //---

        $request = $e->getApplication()->getServiceManager()->get('Request');

        if (!($request instanceof \Zend\Console\Request)) {
            // Setup authentication listener...
            $eventManager->attach(MvcEvent::EVENT_ROUTE, [new AuthenticationListener, 'authenticate'], 500);

            // Register error handler for dispatch and render errors
            $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleError'));
            $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'handleError'));
        }
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                'DynamoCronLock' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config')['cron']['lock']['dynamodb'];

                    $config['keyPrefix'] = $sm->get('config')['stack']['name'];

                    return new DynamoCronLock($config);
                },

                'DynamoQueueClient' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config');
                    $dynamoConfig = $config['pdf']['DynamoQueue'];

                    $dynamoDb = new DynamoDbClient($dynamoConfig['client']);

                    return new DynamoQueue($dynamoDb, $dynamoConfig['settings']);
                },

                'Zend\Authentication\AuthenticationService' => function ($sm) {
                    // NonPersistent persists only for the life of the request...
                    return new AuthenticationService(new NonPersistent());
                },




                // Create an instance of the MongoClient...
                Mongo\ManagerFactory::class . '-default' => new Mongo\ManagerFactory('default'),
                Mongo\ManagerFactory::class . '-auth' => new Mongo\ManagerFactory('auth'),

                // Connect the above MongoClient to a DB...
                Mongo\DatabaseFactory::class . '-default' => new Mongo\DatabaseFactory('default'),
                Mongo\DatabaseFactory::class . '-auth' => new Mongo\DatabaseFactory('auth'),



                //  Access collections within the above opglpa-api DB
                Mongo\CollectionFactory::class . '-api-lpa' => new Mongo\CollectionFactory('lpa'),
                Mongo\CollectionFactory::class . '-api-user' => new Mongo\CollectionFactory('user'),
                Mongo\CollectionFactory::class . '-api-stats-who' => new Mongo\CollectionFactory('whoAreYou'),
                Mongo\CollectionFactory::class . '-api-stats-lpas' => new Mongo\CollectionFactory('lpaStats'),







                AuthUserCollection::class => function (ContainerInterface $container) {
                    $mcf = new Mongo\CollectionFactory('user', 'auth');

                    $authUserCollection = $mcf->__invoke($container, 'TODO');

                    return new AuthUserCollection($authUserCollection);
                },
                AuthLogCollection::class => function (ContainerInterface $container) {
                    $mcf = new Mongo\CollectionFactory('log', 'auth');

                    $authLogCollection = $mcf->__invoke($container, 'TODO');

                    return new AuthLogCollection($authLogCollection);
                },






                // Get S3Client Client
                'S3Client' => function ($sm) {
                    $config = $sm->get('config');

                    return new S3Client($config['pdf']['cache']['s3']['client']);
                },

            ], // factories
        ];
    } // function

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Use our logger to send this exception to its various destinations
     * Listen for and catch ApiProblemExceptions. Convert them to a standard ApiProblemResponse.
     *
     * @param MvcEvent $e
     * @return ApiProblemResponse
     */
    public function handleError(MvcEvent $e)
    {
        // Marshall an ApiProblem and view model based on the exception
        $exception = $e->getParam('exception');

        if ($exception instanceof ApiProblemExceptionInterface) {
            $problem = new ApiProblem($exception->getCode(), $exception->getMessage());

            $e->stopPropagation();
            $response = new ApiProblemResponse($problem);
            $e->setResponse($response);

            $logger = Logger::getInstance();
            $logger->err($exception->getMessage());

            return $response;
        }
    }
}
