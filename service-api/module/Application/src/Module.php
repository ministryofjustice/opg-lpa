<?php

namespace Application;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Handler\PingHandler;
use Application\Handler\PingHandlerFactory;
use Application\Library\Authentication\AuthenticationListener;
use Application\Library\Listener\ContentNegotiationListener;
use Application\Library\Listener\ErrorListener;
use Application\Model\DataAccess\Postgres;
use Application\Model\DataAccess\Repository;
use Application\Model\Service\Authentication\Service as AppAuthenticationService;
use Application\Model\Service\Feedback\FeedbackValidator;
use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Aws\Signature\SignatureV4;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\HttpClient;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use MakeShared\Logging\LoggerFactory;
use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use PDO;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress UnusedClass
 */
class Module
{
    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $sm = $e->getApplication()->getServiceManager();

        $sm->get(ModuleRouteListener::class)->attach($eventManager);
        $sm->get(ContentNegotiationListener::class)->attach($eventManager);
        $sm->get(AuthenticationListener::class)->attach($eventManager);
        $sm->get(ErrorListener::class)->attach($eventManager);
    }

    /**
     * @return array
     */
    public function getServiceConfig(): array
    {
        // calls to $sm->get('config') return the array in
        // service-api/config/autoload/global.php
        return [
            'aliases' => [
                // Map the Repository Interfaces to concrete implementations.
                Repository\User\LogRepositoryInterface::class => Postgres\LogData::class,
                Repository\User\UserRepositoryInterface::class => Postgres\UserData::class,
                Repository\Stats\StatsRepositoryInterface::class => Postgres\StatsData::class,
                Repository\Application\WhoRepositoryInterface::class => Postgres\WhoAreYouData::class,
                Repository\Application\ApplicationRepositoryInterface::class => Postgres\ApplicationData::class,
                Repository\Feedback\FeedbackRepositoryInterface::class => Postgres\FeedbackData::class,
                Repository\SharedSpace\SharedSpaceRepositoryInterface::class => Postgres\SharedSpaceData::class,
                ServiceLocatorInterface::class => 'ServiceManager',
                LoggerInterface::class => 'Logger',
                ClientInterface::class => Client::class,
                'FeedbackValidator' => FeedbackValidator::class,
            ],
            'invokables' => [
                HttpClient::class => Guzzle7Client::class,
            ],
            'factories' => [
                'Logger' => LoggerFactory::class,

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

                'Laminas\Authentication\AuthenticationService' => function () {
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
                Postgres\SharedSpaceData::class => Postgres\DataFactory::class,


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

                'AwsCredentials' => function () {
                    $provider = CredentialProvider::defaultProvider();
                    return $provider()->wait();
                },

                'AwsApiGatewaySignature' => function () {
                    return new SignatureV4('execute-api', self::awsRegion());
                },

                'AppAuthenticationService' => function ($sm) {
                    return new AppAuthenticationService(
                        $sm->get(Repository\SharedSpace\SharedSpaceRepositoryInterface::class),
                        $sm->get('config')['session']['token_ttl'],
                        $sm->get('config')['session']['log_salt']
                    );
                },

                'TelemetryTracer' => function ($sm) {
                    $telemetryConfig = $sm->get('config')['telemetry'];
                    return Tracer::create($sm->get(ExporterFactory::class), $telemetryConfig);
                },

                PingHandler::class => PingHandlerFactory::class,

            ], // factories
        ];
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public static function awsRegion(): string
    {
        $region = getenv('AWS_REGION');
        return $region !== false ? $region : 'eu-west-1';
    }
}
