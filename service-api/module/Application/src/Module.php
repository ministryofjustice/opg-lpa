<?php

namespace Application;

use GuzzleHttp\Client;
use PDO;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Application\Model\DataAccess\Repository;
use Application\Model\DataAccess\Postgres;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use Application\Library\Authentication\AuthenticationListener;
use Alphagov\Notifications\Client as NotifyClient;
use Aws\Sns\SnsClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Aws\Signature\SignatureV4;
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
                // Map the Repository Interfaces to concrete implementations.
                Repository\User\LogRepositoryInterface::class => Postgres\LogData::class,
                Repository\User\UserRepositoryInterface::class => Postgres\UserData::class,
                Repository\Stats\StatsRepositoryInterface::class => Postgres\StatsData::class,
                Repository\Application\WhoRepositoryInterface::class => Postgres\WhoAreYouData::class,
                Repository\Application\ApplicationRepositoryInterface::class => Postgres\ApplicationData::class,
                Repository\Feedback\FeedbackRepositoryInterface::class => Postgres\FeedbackData::class,
            ],
            'invokables' => [
                HttpClient::class => Guzzle6Client::class,
                Client::class => Client::class,
            ],
            'factories' => [
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

                'ZendDbAdapter' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config');
                    if (!isset($config['db']['postgres']['default'])) {
                        throw new \RuntimeException("Missing Postgres configuration");
                    }

                    $dbconf = $config['db']['postgres']['default'];
                    $dsn = "{$dbconf['adapter']}:host={$dbconf['host']};port={$dbconf['port']};dbname={$dbconf['dbname']}";

                    return new ZendDbAdapter([
                        'dsn' => $dsn,
                        'driver' => 'pdo',
                        'username' => $dbconf['username'],
                        'password' => $dbconf['password'],
                        'driver_options' => [
                            // RDS doesn't play well with persistent connections
                            PDO::ATTR_PERSISTENT => false,
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        ],
                    ]);
                },

                'Zend\Authentication\AuthenticationService' => function ($sm) {
                    // NonPersistent persists only for the life of the request...
                    return new AuthenticationService(new NonPersistent());
                },

                // Postgres data factories
                Postgres\ApplicationData::class => Postgres\DataFactory::class,
                Postgres\UserData::class        => Postgres\DataFactory::class,
                Postgres\LogData::class         => Postgres\DataFactory::class,
                Postgres\StatsData::class       => Postgres\DataFactory::class,
                Postgres\WhoAreYouData::class   => Postgres\DataFactory::class,
                Postgres\FeedbackData::class    => Postgres\DataFactory::class,

                // Get S3Client Client
                'S3Client' => function ($sm) {
                    $config = $sm->get('config');

                    return new S3Client($config['pdf']['cache']['s3']['client']);
                },

                // Get S3Client Client
                'SqsClient' => function ($sm) {
                    $config = $sm->get('config');

                    if (!isset($config['pdf']['queue']['sqs']['client'])) {
                        throw new \RuntimeException("Missing SQS configuration");
                    }

                    return new SqsClient($config['pdf']['queue']['sqs']['client']);
                },

                'AwsApiGatewaySignature' => function ($sm) {
                    return new SignatureV4('execute-api', 'eu-west-1');
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
