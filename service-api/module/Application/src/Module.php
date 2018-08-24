<?php

namespace Application;

use Application\Model\DataAccess\Repository;
use Application\Model\DataAccess\Mongo;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use Application\Library\Authentication\AuthenticationListener;
use Application\Model\Service\System\DynamoCronLock;
use Alphagov\Notifications\Client as NotifyClient;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Sns\SnsClient;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use Http\Adapter\Guzzle6\Client as Guzzle6Client;
use Http\Client\HttpClient;
use Opg\Lpa\Logger\Logger;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Console\Request as ConsoleRequest;
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

        $request = $e->getApplication()->getServiceManager()->get('Request');

        if (!$request instanceof ConsoleRequest) {
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
            'aliases' => [
                Repository\User\UserRepositoryInterface::class  => Mongo\Collection\AuthUserCollection::class,
                Repository\User\LogRepositoryInterface::class   => Mongo\Collection\AuthLogCollection::class,
                Repository\Application\WhoRepositoryInterface::class  => Mongo\Collection\ApiWhoCollection::class,
                Repository\Stats\StatsRepositoryInterface::class  => Mongo\Collection\ApiStatsLpasCollection::class,
            ],
            'invokables' => [
                HttpClient::class => Guzzle6Client::class,
            ],
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

                'NotifyClient' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config');

                    return new NotifyClient([
                        'apiKey' => $config['notify']['api']['key'],
                        'httpClient' => $sm->get(HttpClient::class)
                    ]);
                },

                'SnsClient' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config')['log']['sns'];

                    return new SnsClient($config['client']);
                },

                'Zend\Authentication\AuthenticationService' => function ($sm) {
                    // NonPersistent persists only for the life of the request...
                    return new AuthenticationService(new NonPersistent());
                },

                //  Mongo database
                Mongo\DatabaseFactory::class . '-default'   => Mongo\DatabaseFactory::class,
                Mongo\DatabaseFactory::class . '-auth'      => Mongo\DatabaseFactory::class,

                //  Collection wrappers for Mongo collection
                Mongo\Collection\ApiLpaCollection::class        => Mongo\Collection\CollectionFactory::class,
                Mongo\Collection\ApiStatsLpasCollection::class  => Mongo\Collection\CollectionFactory::class,
                Mongo\Collection\ApiUserCollection::class       => Mongo\Collection\CollectionFactory::class,
                Mongo\Collection\ApiWhoCollection::class        => Mongo\Collection\CollectionFactory::class,
                Mongo\Collection\AuthLogCollection::class       => Mongo\Collection\CollectionFactory::class,
                Mongo\Collection\AuthUserCollection::class      => Mongo\Collection\CollectionFactory::class,

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
