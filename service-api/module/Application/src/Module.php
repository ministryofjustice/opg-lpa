<?php

namespace Application;

use ArrayIterator;
use GuzzleHttp\Client;
use Alphagov\Notifications\Client as NotifyClient;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemExceptionInterface;
use Application\Library\Authentication\AuthenticationListener;
use Application\Model\DataAccess\Postgres;
use Application\Model\DataAccess\Repository;
use Application\Model\Service\Authentication\Service as AppAuthenticationService;
use Application\Model\Service\Feedback\FeedbackValidator;
use Aws\Credentials\CredentialProvider;
use Aws\Sns\SnsClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Aws\Signature\SignatureV4;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\HttpClient;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Laminas\Http\Header\Accept as AcceptHeader;
use Laminas\Http\Request as LaminasRequest;
use Laminas\Http\Response as LaminasResponse;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use PDO;
use Psr\Log\LoggerAwareInterface;

class Module
{
    public const VERSION = '3.0.3-dev';

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'negotiateContent'], 1000);

        // Setup authentication listener...
        $sm = $e->getApplication()->getServiceManager();
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$sm->get(AuthenticationListener::class), 'authenticate'], 500);


        // Register error handler for dispatch and render errors;
        // priority is set to 100 here so that the global MvcEventListener
        // has a chance to log it before it's converted into an API exception
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleError'), 100);
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'handleError'), 100);
    }

    public function getServiceConfig()
    {
        // calls to $sm->get('config') return the array in
        // service-api/config/autoload/global.php
        return [
            'abstract_factories' => [
                ReflectionBasedAbstractFactory::class,
            ],
            'aliases' => [
                // Map the Repository Interfaces to concrete implementations.
                Repository\User\LogRepositoryInterface::class => Postgres\LogData::class,
                Repository\User\UserRepositoryInterface::class => Postgres\UserData::class,
                Repository\Stats\StatsRepositoryInterface::class => Postgres\StatsData::class,
                Repository\Application\WhoRepositoryInterface::class => Postgres\WhoAreYouData::class,
                Repository\Application\ApplicationRepositoryInterface::class => Postgres\ApplicationData::class,
                Repository\Feedback\FeedbackRepositoryInterface::class => Postgres\FeedbackData::class,
                ServiceLocatorInterface::class => 'ServiceManager',
            ],
            'invokables' => [
                HttpClient::class => Guzzle7Client::class,
                Client::class => Client::class,
            ],
            'factories' => [
                'Logger'          => 'MakeShared\Logging\LoggerFactory',
                'ExporterFactory' => ReflectionBasedAbstractFactory::class,

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
                    $dsn = "{$dbconf['adapter']}:host={$dbconf['host']};" .
                        "port={$dbconf['port']};dbname={$dbconf['dbname']}";

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

                'Laminas\Authentication\AuthenticationService' => function ($sm) {
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

                'AwsCredentials' => function ($sm) {
                    $provider = CredentialProvider::defaultProvider();
                    return $provider()->wait();
                },

                'AwsApiGatewaySignature' => function ($sm) {
                    return new SignatureV4('execute-api', 'eu-west-1');
                },

                'AppAuthenticationService' => function ($sm) {
                    return new AppAuthenticationService($sm->get('config')['session']['token_ttl']);
                },

                'FeedbackValidator' => function () {
                    return new FeedbackValidator();
                },

                'TelemetryTracer' => function ($sm) {
                    $telemetryConfig = $sm->get('config')['telemetry'];
                    return Tracer::create($sm->get(ExporterFactory::class), $telemetryConfig);
                },

            ], // factories
            'initializers' => [
                function(ServiceLocatorInterface $container, $instance) {
                    if (! $instance instanceof LoggerAwareInterface) {
                        return;
                    }
                    $instance->setLogger($container->get('Logger'));
                }
            ]

        ];
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listen for and catch ApiProblemExceptions. Convert them to a standard ApiProblemResponse.
     *
     * @param MvcEvent $e
     * @return ApiProblemResponse|null
     */
    public function handleError(MvcEvent $e)
    {
        $response = null;

        // Marshall an ApiProblem and view model based on the exception
        $exception = $e->getParam('exception');

        if ($exception instanceof ApiProblemExceptionInterface) {
            $problem = new ApiProblem($exception->getCode(), $exception->getMessage());
            $response = new ApiProblemResponse($problem);

            $e->stopPropagation();
            $response = new ApiProblemResponse($problem);
            $e->setResponse($response);
        }

        return $response;
    }

    // if the client's Accept header doesn't match the content type on
    // the response, send a `406 Not acceptable` response
    public function negotiateContent(MvcEvent $e)
    {
        /** @var LaminasRequest */
        $request = $e->getRequest();

        /** @var LaminasResponse */
        $response = $e->getResponse();

        /** @var AcceptHeader */
        $requestAcceptHeader = $request->getHeader('accept');

        // typically a response will only have one content-type header,
        // but just in case something weird happens we'll loop over the values;
        // NB might also return false, which is OK because AcceptHeader->match()
        // will count that as a failed match
        $responseContentTypes = $response->getHeaders()->get('content-type');
        if ($responseContentTypes === false) {
            // no content type in the response; this will give a 406 as
            // the client's Accept header can't be matched to nothing
            $responseContentTypes = [];
        } elseif (!is_a($responseContentTypes, ArrayIterator::class)) {
            $responseContentTypes = new ArrayIterator([$responseContentTypes]);
        }

        $ok = false;
        foreach (iterator_to_array($responseContentTypes) as $responseContentType) {
            $responseContentTypeValue = $responseContentType->getFieldValue();

            if (
                !empty($responseContentTypeValue) &&
                $requestAcceptHeader->match($responseContentTypeValue)
            ) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            $e->setResponse(new ApiProblemResponse(
                new ApiProblem(406, 'Response has a content type which is not acceptable to the client')
            ));
        }
    }
}
