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
use Laminas\ServiceManager\ServiceLocatorInterface;
use MakeShared\Telemetry\Tracer;
use PDO;

class Module
{
    public const VERSION = '3.0.3-dev';

    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'negotiateContent'], 1000);

        // Setup authentication listener...
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [new AuthenticationListener(), 'authenticate'], 500);

        // Register error handler for dispatch and render errors;
        // priority is set to 100 here so that the global MvcEventListener
        // has a chance to log it before it's converted into an API exception
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleError'), 100);
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'handleError'), 100);
    }

    /**
     * @return (\Closure|string)[][]
     *
     * @psalm-return array{aliases: array{'Application\\Model\\DataAccess\\Repository\\User\\LogRepositoryInterface'::class: Model\DataAccess\Postgres\LogData::class, 'Application\\Model\\DataAccess\\Repository\\User\\UserRepositoryInterface'::class: Model\DataAccess\Postgres\UserData::class, 'Application\\Model\\DataAccess\\Repository\\Stats\\StatsRepositoryInterface'::class: Model\DataAccess\Postgres\StatsData::class, 'Application\\Model\\DataAccess\\Repository\\Application\\WhoRepositoryInterface'::class: Model\DataAccess\Postgres\WhoAreYouData::class, 'Application\\Model\\DataAccess\\Repository\\Application\\ApplicationRepositoryInterface'::class: Model\DataAccess\Postgres\ApplicationData::class, 'Application\\Model\\DataAccess\\Repository\\Feedback\\FeedbackRepositoryInterface'::class: Model\DataAccess\Postgres\FeedbackData::class}, invokables: array{'Http\\Client\\HttpClient'::class: Guzzle7Client::class, 'GuzzleHttp\\Client'::class: Client::class}, factories: array{NotifyClient: \Closure(ServiceLocatorInterface):NotifyClient, SnsClient: \Closure(ServiceLocatorInterface):SnsClient, ZendDbAdapter: \Closure(ServiceLocatorInterface):ZendDbAdapter, 'Laminas\\Authentication\\AuthenticationService': \Closure(mixed):AuthenticationService, 'Application\\Model\\DataAccess\\Postgres\\ApplicationData'::class: Model\DataAccess\Postgres\DataFactory::class, 'Application\\Model\\DataAccess\\Postgres\\UserData'::class: Model\DataAccess\Postgres\DataFactory::class, 'Application\\Model\\DataAccess\\Postgres\\LogData'::class: Model\DataAccess\Postgres\DataFactory::class, 'Application\\Model\\DataAccess\\Postgres\\StatsData'::class: Model\DataAccess\Postgres\DataFactory::class, 'Application\\Model\\DataAccess\\Postgres\\WhoAreYouData'::class: Model\DataAccess\Postgres\DataFactory::class, 'Application\\Model\\DataAccess\\Postgres\\FeedbackData'::class: Model\DataAccess\Postgres\DataFactory::class, S3Client: \Closure(mixed):S3Client, SqsClient: \Closure(mixed):SqsClient, AwsCredentials: \Closure(mixed):mixed, AwsApiGatewaySignature: \Closure(mixed):SignatureV4, AppAuthenticationService: \Closure(mixed):AppAuthenticationService, FeedbackValidator: \Closure():FeedbackValidator, TelemetryTracer: \Closure(mixed):mixed}}
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
            ],
            'invokables' => [
                HttpClient::class => Guzzle7Client::class,
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
                    return Tracer::create($telemetryConfig);
                },

            ], // factories

        ];
    }

    /**
     * @return ((((((((((string|string[])[]|string)[]|string)[]|string|true)[]|string)[]|string|true)[]|string)[]|string|true)[]|string)[]|string|true)[][]
     *
     * @psalm-return array{router: array{routes: array{home: array{type: 'Laminas\Router\Http\Literal', options: array{route: '/', defaults: array{controller: 'Application\Controller\Index', action: 'index'}}}, ping: array{type: 'Laminas\Router\Http\Segment', options: array{route: '/ping[/:action]', defaults: array{controller: 'Application\Controller\Ping', action: 'index'}}}, stats: array{type: 'Segment', options: array{route: '/stats/:type', constraints: array{type: '[a-z0-9][a-z0-9-]*'}, defaults: array{controller: 'Application\Controller\Stats'}}}, feedback: array{type: 'Literal', options: array{route: '/user-feedback', defaults: array{controller: 'Application\Controller\Feedback'}}}, 'auth-routes': array{type: 'Segment', options: array{route: '/v2', defaults: array{__NAMESPACE__: 'Application\Controller\Version2\Auth'}}, child_routes: array{authenticate: array{type: 'Segment', options: array{route: '/authenticate', defaults: array{controller: 'AuthenticateController', action: 'authenticate'}}}, 'session-expiry': array{type: 'Segment', options: array{route: '/session-expiry', defaults: array{controller: 'AuthenticateController', action: 'sessionExpiry'}}}, 'session-set-expiry': array{type: 'Segment', options: array{route: '/session-set-expiry', defaults: array{controller: 'AuthenticateController', action: 'setSessionExpiry'}}}, users: array{type: 'Segment', options: array{route: '/users', defaults: array{controller: 'UsersController'}}, may_terminate: true, child_routes: array{'search-users': array{type: 'Segment', options: array{route: '/search', defaults: array{action: 'search'}}}, 'match-users': array{type: 'Segment', options: array{route: '/match', defaults: array{action: 'match'}}}, 'email-change': array{type: 'Segment', options: array{route: '/:userId/email', constraints: array{userId: '[a-zA-Z0-9]+'}, defaults: array{controller: 'EmailController', action: 'change'}}}, 'email-verify': array{type: 'Segment', options: array{route: '/email', defaults: array{controller: 'EmailController', action: 'verify'}}}, 'password-change': array{type: 'Segment', options: array{route: '[/:userId]/password', constraints: array{userId: '[a-zA-Z0-9]+'}, defaults: array{controller: 'PasswordController', action: 'change'}}}, 'password-reset': array{type: 'Segment', options: array{route: '/password-reset', defaults: array{controller: 'PasswordController', action: 'reset'}}}}}}}, 'api-routes': array{type: 'Segment', options: array{route: '/v2', defaults: array{__NAMESPACE__: 'Application\Controller\Version2\Lpa'}}, may_terminate: true, child_routes: array{user: array{type: 'Segment', options: array{route: '/user/:userId', constraints: array{userId: '[a-f0-9]+'}, defaults: array{controller: 'UserController'}}, may_terminate: true, child_routes: array{statuses: array{type: 'Segment', options: array{route: '/statuses/:lpaIds', constraints: array{lpaIds: '[0-9,]+'}, defaults: array{__NAMESPACE__: '', controller: Controller\StatusController::class}}}, applications: array{type: 'Segment', options: array{route: '/applications[/:lpaId]', constraints: array{lpaId: '[0-9]+'}, defaults: array{controller: 'ApplicationController'}}, may_terminate: true, child_routes: array{'certificate-provider': array{type: 'Literal', options: array{route: '/certificate-provider', defaults: array{controller: 'CertificateProviderController'}}}, correspondent: array{type: 'Literal', options: array{route: '/correspondent', defaults: array{controller: 'CorrespondentController'}}}, donor: array{type: 'Literal', options: array{route: '/donor', defaults: array{controller: 'DonorController'}}}, instruction: array{type: 'Literal', options: array{route: '/instruction', defaults: array{controller: 'InstructionController'}}}, lock: array{type: 'Literal', options: array{route: '/lock', defaults: array{controller: 'LockController'}}}, 'notified-people': array{type: 'Segment', options: array{route: '/notified-people[/:notifiedPersonId]', constraints: array{notifiedPersonId: '[0-9]+'}, defaults: array{controller: 'NotifiedPeopleController'}}}, payment: array{type: 'Literal', options: array{route: '/payment', defaults: array{controller: 'PaymentController'}}}, pdfs: array{type: 'Segment', options: array{route: '/pdfs/:pdfType', constraints: array{pdfType: '[a-z0-9][a-z0-9.]*'}, defaults: array{controller: 'PdfController'}}}, preference: array{type: 'Literal', options: array{route: '/preference', defaults: array{controller: 'PreferenceController'}}}, 'primary-attorneys': array{type: 'Segment', options: array{route: '/primary-attorneys[/:primaryAttorneyId]', constraints: array{primaryAttorneyId: '[0-9]+'}, defaults: array{controller: 'PrimaryAttorneyController'}}}, 'primary-attorney-decisions': array{type: 'Literal', options: array{route: '/primary-attorney-decisions', defaults: array{controller: 'PrimaryAttorneyDecisionsController'}}}, 'repeat-case-number': array{type: 'Literal', options: array{route: '/repeat-case-number', defaults: array{controller: 'RepeatCaseNumberController'}}}, 'replacement-attorneys': array{type: 'Segment', options: array{route: '/replacement-attorneys[/:replacementAttorneyId]', constraints: array{replacementAttorneyId: '[0-9]+'}, defaults: array{controller: 'ReplacementAttorneyController'}}}, 'replacement-attorney-decisions': array{type: 'Literal', options: array{route: '/replacement-attorney-decisions', defaults: array{controller: 'ReplacementAttorneyDecisionsController'}}}, seed: array{type: 'Literal', options: array{route: '/seed', defaults: array{controller: 'SeedController'}}}, type: array{type: 'Literal', options: array{route: '/type', defaults: array{controller: 'TypeController'}}}, 'who-are-you': array{type: 'Literal', options: array{route: '/who-are-you', defaults: array{controller: 'WhoAreYouController'}}}, 'who-is-registering': array{type: 'Literal', options: array{route: '/who-is-registering', defaults: array{controller: 'WhoIsRegisteringController'}}}}}}}}}}}, lmc_rbac: array{assertion_map: array{isAuthorizedToManageUser: 'Application\Library\Authorization\Assertions\IsAuthorizedToManageUser'}, role_provider: array{'LmcRbacMvc\\Role\\InMemoryRoleProvider': array{admin: array{children: list{'user'}, permissions: list{'admin'}}, user: array{children: list{'guest'}, permissions: list{'authenticated', 'isAuthorizedToManageUser'}}, service: array{children: list{'guest'}, permissions: list{'authenticated', 'isAuthorizedToManageUser'}}, guest: array{permissions: list{'stats'}}}}}, controllers: array{invokables: array{'Application\\Controller\\Index': 'Application\Controller\IndexController'}, factories: array{'Application\\Controller\\Ping': ControllerFactory\PingControllerFactory::class, 'Application\\Controller\\Stats': ControllerFactory\StatsControllerFactory::class, 'Application\\Controller\\Feedback': ControllerFactory\FeedbackControllerFactory::class, 'Application\\Controller\\StatusController'::class: ControllerFactory\StatusControllerFactory::class}, abstract_factories: list{'Application\ControllerFactory\AuthControllerAbstractFactory', 'Application\ControllerFactory\LpaControllerAbstractFactory'}}, service_manager: array{abstract_factories: list{'Application\Model\Service\ServiceAbstractFactory', 'Laminas\Cache\Service\StorageCacheAbstractServiceFactory'}, aliases: array{translator: 'MvcTranslator'}, factories: array{'Application\\Command\\GenerateStatsCommand': 'Application\Command\GenerateStatsCommand', 'Application\\Command\\AccountCleanupCommand': 'Application\Command\AccountCleanupCommand', 'Application\\Command\\LockCommand': 'Application\Command\LockCommand'}}, translator: array{locale: 'en_US', translation_file_patterns: list{array{type: 'gettext', base_dir: '/Users/nicholasdavis/gitrepos/opg-lpa/service-api/module/Application/config/../language', pattern: '%s.mo'}}}, view_manager: array{display_not_found_reason: true, display_exceptions: true, doctype: 'HTML5', not_found_template: 'error/404', exception_template: 'error/index', template_map: array{'layout/layout': '/Users/nicholasdavis/gitrepos/opg-lpa/service-api/module/Application/config/../view/layout/layout.phtml', 'application/index/index': '/Users/nicholasdavis/gitrepos/opg-lpa/service-api/module/Application/config/../view/application/index/index.phtml', 'error/404': '/Users/nicholasdavis/gitrepos/opg-lpa/service-api/module/Application/config/../view/error/404.phtml', 'error/index': '/Users/nicholasdavis/gitrepos/opg-lpa/service-api/module/Application/config/../view/error/index.phtml'}, template_path_stack: list{'/Users/nicholasdavis/gitrepos/opg-lpa/service-api/module/Application/config/../view'}, strategies: list{'ViewJsonStrategy'}}, 'laminas-cli': array{commands: array{'service-api:generate-stats': Command\GenerateStatsCommand::class, 'service-api:account-cleanup': Command\AccountCleanupCommand::class, 'service-api:lock': Command\LockCommand::class}}}
     */
    public function getConfig(): array
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
    public function negotiateContent(MvcEvent $e): void
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
