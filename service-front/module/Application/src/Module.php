<?php

namespace Application;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\Form\AbstractCsrfForm;
use Application\Form\Element\CsrfBuilder;
use Application\Handler\PingHandler;
use Application\Handler\PingHandlerFactory;
use Application\Handler\PingHandlerJson;
use Application\Handler\PingHandlerJsonFactory;
use Application\Handler\PingHandlerPingdom;
use Application\Handler\PingHandlerPingdomFactory;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\Session\SessionManager;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Redis\RedisClient;
use Application\Model\Service\Session\FilteringSaveHandler;
use Application\Model\Service\Session\PersistentSessionDetails;
use Alphagov\Pay\Client as GovPayClient;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\ModuleManager\Feature\FormElementProviderInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Container;
use Laminas\Stdlib\ArrayUtils;
use Laminas\View\Model\ViewModel;
use Psr\Log\LoggerAwareInterface;
use Redis;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class Module implements FormElementProviderInterface
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // Register error handler for dispatch and render errors
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'handleError']);
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_RENDER_ERROR, [$this, 'handleError']);
        $eventManager->attach(\Laminas\Mvc\MvcEvent::EVENT_RENDER, [$this, 'preRender']);

        register_shutdown_function(function () {
            $error = error_get_last();

            if (($error['type'] ?? null) === E_ERROR) {
                // This is a fatal error, we have no exception and no nice view to render
                // The fatal error will have been logged already prior to writing this message
                echo 'An unknown server error has occurred.';
            }
        });

        $request = $e->getApplication()->getServiceManager()->get('Request');

        $path = $request->getUri()->getPath();

        // Only bootstrap the session if it's *not* PHPUnit AND is not an excluded url.
        if (
            !strstr($request->getServer('SCRIPT_NAME'), 'phpunit') &&
            !in_array($path, [
                // URLs excluded from creating a session
                '/ping/elb',
                '/ping/json',
            ])
        ) {
            $this->bootstrapSession($e);
            $this->bootstrapIdentity($e, $path != '/session-state');
        }
    }

    /**
     * Sets up and starts global sessions.
     *
     * @param MvcEvent $e
     */
    private function bootstrapSession(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();

        /** @var SessionManager $session */
        $session = $sm->get('SessionManager');

        // Always starts the session.
        $session->start();

        // Ensures this SessionManager is used for all Session Containers.
        Container::setDefaultManager($session);

        $sm->get(SessionManagerSupport::class)->initialise();
    }

    /**
     *
     * This now checks the token on every request otherwise we have no method of knowing if the user has
     * logged in on another browser.
     *
     * We don't deal with forcing the user to re-authenticate here as they
     * may be accessing a page that does not require authentication.
     *
     * @param MvcEvent $e
     */
    private function bootstrapIdentity(MvcEvent $e, bool $updateToken = true)
    {
        $sm = $e->getApplication()->getServiceManager();

        $auth = $sm->get('AuthenticationService');
        /** @var Identity $identity */
        $identity = $auth->getIdentity();

        //  If there is an identity (logged in user) then get the token details and check to see if it has expired
        if (!is_null($identity) && $updateToken) {
            try {
                $info = $sm->get('UserService')->getTokenInfo($identity->token());

                if ($info['success'] && !is_null($info['expiresIn'])) {
                    // update the time the token expires in the session
                    $identity->tokenExpiresIn($info['expiresIn']);
                } else {
                    $auth->clearIdentity();

                    // Record that identity was cleared because of a 500 error (normally db-related)
                    if ($info['failureCode'] >= 500) {
                        $authFailureReason = new Container('AuthFailureReason');
                        $authFailureReason->reason = 'Internal system error';
                        $authFailureReason->code = $info['failureCode'];
                    }
                }
            } catch (ApiException $ex) {
                $auth->clearIdentity();
            }
        }
    }

    public function getServiceConfig()
    {
        return [
            'shared' => [
                'HttpClient' => false,
            ],
            'aliases' => [
                'AddressLookup' => 'OrdnanceSurvey',
                'Laminas\Authentication\AuthenticationService' => 'AuthenticationService',
                ServiceLocatorInterface::class => ServiceManager::class,
            ],
            'factories' => [
                'ApiClient'             => 'Application\Model\Service\ApiClient\ClientFactory',
                'AuthenticationService' => 'Application\Model\Service\Authentication\AuthenticationServiceFactory',
                'OrdnanceSurvey'        => 'Application\Model\Service\AddressLookup\OrdnanceSurveyFactory',
                'SessionManager'        => 'Application\Model\Service\Session\SessionFactory',
                'MailTransport'         => 'Application\Model\Service\Mail\Transport\MailTransportFactory',
                'Logger'                => 'MakeShared\Logging\LoggerFactory',
                SessionManagerSupport::class => function (ServiceLocatorInterface $sm) {
                    return new SessionManagerSupport($sm->get('SessionManager'));
                },

                'ExporterFactory'       => ReflectionBasedAbstractFactory::class,

                // Authentication Adapter
                'LpaAuthAdapter' => function (ServiceLocatorInterface $sm) {
                    return new LpaAuthAdapter($sm->get('ApiClient'));
                },

                // Generate the session container for a user's personal details
                'UserDetailsSession' => function () {
                    return new Container('UserDetails');
                },

                // Creates new container to store additional session information
                'PersistentSessionDetails' => function (ServiceLocatorInterface $sm) {
                    $route = $sm->get('Application')->getMvcEvent()->getRouteMatch();

                    return new PersistentSessionDetails($route);
                },

                // PSR-7 HTTP Client
                'HttpClient' => function () {
                    return new \Http\Adapter\Guzzle7\Client();
                },

                // required for the system message, set in admin UI
                'DynamoDbSystemMessageCache' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config')['admin']['dynamodb'];

                    $config['keyPrefix'] = $sm->get('config')['stack']['name'];

                    $dynamoDbAdapter = new DynamoDbKeyValueStore($config);
                    $dynamoDbAdapter->setDynamoDbClient($sm->get('DynamoDbSystemMessageClient'));

                    return $dynamoDbAdapter;
                },

                'DynamoDbSystemMessageClient' => function (ServiceLocatorInterface $sm) {
                    return new DynamoDbClient($sm->get('config')['admin']['dynamodb']['client']);
                },

                'GovPayClient' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config')['alphagov']['pay'];

                    return new GovPayClient([
                        'apiKey' => $config['key'],
                        'httpClient' => $sm->get('HttpClient'),
                        'baseUrl'  => $config['url'],
                    ]);
                },

                'RedisClient' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('config');

                    $redisUrl = $config['redis']['url'];
                    $ttlMs = $config['redis']['ttlMs'];

                    return new RedisClient($redisUrl, $ttlMs, new Redis());
                },

                'SaveHandler' => function (ServiceLocatorInterface $sm) {
                    $redisClient = $sm->get('RedisClient');
                    $request = $sm->get('Request');

                    $filter = function () use ($request) {
                        return !$request->getHeaders()->has('X-SessionReadOnly');
                    };

                    return new FilteringSaveHandler($redisClient, [$filter]);
                },

                'TwigViewRenderer' => function (ServiceLocatorInterface $sm) {
                    $loader = new FilesystemLoader('module/Application/view/application');
                    return new Environment(
                        $loader,
                        [
                            'cache' => $sm->get('config')['twig']['cache_dir']
                        ]
                    );
                },

                'TelemetryTracer' => function ($sm) {
                    $telemetryConfig = $sm->get('config')['telemetry'];
                    return Tracer::create($sm->get(ExporterFactory::class), $telemetryConfig);
                },

                'Calculator' => function ($sm) {
                    $fees = $sm->get('config')['fees'] ?? [];
                    return new Calculator($fees);
                },
                PingHandler::class => PingHandlerFactory::class,
                PingHandlerJson::class => PingHandlerJsonFactory::class,
                PingHandlerPingdom::class => PingHandlerPingdomFactory::class,
            ], // factories
            'initializers' => [
                function (ServiceLocatorInterface $container, $instance) {
                    if (! $instance instanceof LoggerAwareInterface) {
                        return;
                    }
                    $instance->setLogger($container->get('Logger'));
                }
            ]
        ];
    }

    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'StaticAssetPath' => function ($sm) {
                    $config = $sm->get('Config');
                    return new \Application\View\Helper\StaticAssetPath($config['version']['cache']);
                },
            ],
        ];
    }

    public function getConfig()
    {
        $configFiles = [
            __DIR__ . '/../config/module.config.php',
            __DIR__ . '/../config/module.routes.php',
        ];

        $config = [];

        // Merge all module config options
        foreach ($configFiles as $configFile) {
            $config = ArrayUtils::merge($config, include($configFile));
        }

        return $config;
    }

    /**
     * Look at the child view of the layout. If we detect that there is
     * a ".twig" file that will be picked up by the Twig module for rendering,
     * then change the current layout to be the ".twig" layout.
     *
     * @param MvcEvent $e
     */
    public function preRender(MvcEvent $e)
    {
        $viewModel = $e->getViewModel();

        if ($viewModel->hasChildren()) {
            // This view has a layout (i.e. it's not a popup window)
            $children = $viewModel->getChildren();

            // $children is an array but we only really expect one child
            $targetTemplateName = $children[0]->getTemplate();

            $potentialTwigTemplate = 'module/Application/view/' . $targetTemplateName . '.twig';

            // if there is a .phtml extension inside the name (abc.phtml.twig), then remove it
            $potentialTwigTemplate = str_replace('.phtml', '', $potentialTwigTemplate);

            // if there is a double .twig extension inside the name (abc.twig.twig), then remove one
            $potentialTwigTemplate = str_replace('.twig.twig', '.twig', $potentialTwigTemplate);

            // the template name will be something like 'application/about-you/index' - with
            // no suffix. We look in the directory where we know the .phtml file will be
            // located and see if there is a .twig file (which would take precedence over it)

            if (file_exists($potentialTwigTemplate)) {
                // Use the Twig layout
                $viewModel->setTemplate('layout/layout');
            }
        }
    }

    /**
     * Show 500 page on MVC exceptions.
     * which is attached to these events in config.
     *
     * @param MvcEvent $e
     * @return ViewModel|null
     */
    public function handleError(MvcEvent $e)
    {
        $exception = $e->getResult()->exception;

        if ($exception) {
            $viewModel = new ViewModel();
            $viewModel->setTemplate('error/500');

            $logger = $e->getApplication()->getServiceManager()->get('Logger');
            $logger->error($exception->getMessage(), [
                'class' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stackTrace' => $exception->getTraceAsString(),
            ]);

            $e->getViewModel()->addChild($viewModel);
            $e->stopPropagation();

            // Suppress psalm errors caused by bug in laminas-mvc;
            // see https://github.com/laminas/laminas-mvc/issues/77
            /**
             * @psalm-suppress UndefinedInterfaceMethod
             */
            $e->getResponse()->setStatusCode(500);

            return $viewModel;
        }
    }

    /**
     * Expected to return \Laminas\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|\Laminas\ServiceManager\Config
     */
    public function getFormElementConfig()
    {
        return [
            'initializers' => [
                'InitCsrfForm' => function (ServiceManager $serviceManager, $form) {
                    if ($form instanceof AbstractCsrfForm) {
                        $form->setCsrf($serviceManager->get(CsrfBuilder::class));
                    }
                },
            ],
        ];
    }
}
