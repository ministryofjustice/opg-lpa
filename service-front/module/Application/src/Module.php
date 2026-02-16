<?php

namespace Application;

use Alphagov\Pay\Client as GovPayClient;
use Application\Handler\AboutYouHandler;
use Application\Handler\Factory\AboutYouHandlerFactory;
use Application\Handler\ChangeEmailAddressHandler;
use Application\Handler\Factory\ChangeEmailAddressHandlerFactory;
use Application\Handler\Factory\HomeRedirectHandlerFactory;
use Application\Handler\HomeHandler;
use Application\Adapter\DynamoDbKeyValueStore;
use Application\Form\AbstractCsrfForm;
use Application\Form\Element\CsrfBuilder;
use Application\Form\Error\FormLinkedErrors;
use Application\Handler\ConfirmRegistrationHandler;
use Application\Handler\AccessibilityHandler;
use Application\Handler\ContactHandler;
use Application\Handler\CookiesHandler;
use Application\Handler\Factory\ConfirmRegistrationHandlerFactory;
use Application\Handler\EnableCookieHandler;
use Application\Handler\Factory\CookiesHandlerFactory;
use Application\Handler\Factory\FeedbackHandlerFactory;
use Application\Handler\Factory\FeedbackThanksHandlerFactory;
use Application\Handler\Factory\GuidanceHandlerFactory;
use Application\Handler\Factory\HomeHandlerFactory;
use Application\Handler\Factory\PingHandlerFactory;
use Application\Handler\Factory\PingHandlerJsonFactory;
use Application\Handler\Factory\PingHandlerPingdomFactory;
use Application\Handler\Factory\RegisterHandlerFactory;
use Application\Handler\Factory\ResendActivationEmailHandlerFactory;
use Application\Handler\FeedbackHandler;
use Application\Handler\FeedbackThanksHandler;
use Application\Handler\GuidanceHandler;
use Application\Handler\HomeRedirectHandler;
use Application\Handler\PingHandler;
use Application\Handler\PingHandlerJson;
use Application\Handler\PingHandlerPingdom;
use Application\Handler\RegisterHandler;
use Application\Handler\ResendActivationEmailHandler;
use Application\Handler\PrivacyHandler;
use Application\Handler\TermsHandler;
use Application\Listener\AuthenticationListener;
use Application\Listener\LpaLoaderListener;
use Application\Listener\LpaViewInjectListener;
use Application\Listener\UserDetailsListener;
use Application\Listener\ViewVariablesListener;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Listener\TermsAndConditionsListener;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Model\Service\Redis\RedisClient;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\FilteringSaveHandler;
use Application\Model\Service\Session\NativeSessionConfig;
use Application\Model\Service\Session\PersistentSessionDetails;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\Session\WritePolicy;
use Application\Service\AccordionService;
use Application\Service\Factory\AccordionServiceFactory;
use Application\Model\Service\User\Details;
use Application\Service\NavigationViewModelHelper;
use Application\Service\Factory\NavigationViewModelHelperFactory;
use Application\Service\Factory\SystemMessageFactory;
use Application\Service\SystemMessage;
use Application\View\Twig\AppFiltersExtension;
use Application\View\Twig\AppFunctionsExtension;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\ModuleManager\Feature\FormElementProviderInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\View\Model\ViewModel;
use MakeShared\Constants;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\Logging\LoggerFactory;
use MakeShared\Telemetry\Exporter\ExporterFactory;
use MakeShared\Telemetry\Tracer;
use Mezzio\Session\Ext\PhpSessionPersistence;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Redis;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Module implements FormElementProviderInterface
{
    public function onBootstrap(MvcEvent $e)
    {
        $application = $e->getApplication();
        $eventManager = $application->getEventManager();
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

        $serviceManager = $application->getServiceManager();
        $request = $application->getRequest();
        $logger = $serviceManager->get(LoggerInterface::class);

        // Add request context to logs as a processor
        if ($request instanceof HttpRequest) {
            $logger->pushProcessor(function ($record) use ($request) {
                $record['extra']['request_path'] = $request->getUri()->getPath();
                $record['extra']['request_method'] = $request->getMethod();

                if ($request->getHeader('X-Request-ID')) {
                    $record['extra'][Constants::TRACE_ID_FIELD_NAME] =  $request->getHeader('X-Request-ID')->getFieldValue() ?? '';
                }

                return $record;
            });

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
                $sessionManager = $this->bootstrapSession($e);
                $this->bootstrapIdentity($e, $path != '/session-state');

                $authenticationService = $serviceManager->get(AuthenticationService::class);
                $sessionUtility = $serviceManager->get(SessionUtility::class);
                $userService = $serviceManager->get(Details::class);
                $config = $serviceManager->get('config');
                $dateService = $serviceManager->get(DateService::class);
                $lpaApplicationService = $serviceManager->get(LpaApplicationService::class);

                // Listeners that run on every request, just before controllers execute (higher priority numbers run first)
                new AuthenticationListener($sessionUtility, $authenticationService)->attach($eventManager, 1003);
                new UserDetailsListener($sessionUtility, $userService, $authenticationService, $sessionManager, $logger)->attach($eventManager, 1002);
                new LpaLoaderListener($authenticationService, $lpaApplicationService)->attach($eventManager, 1001);
                new TermsAndConditionsListener($config, $sessionUtility, $authenticationService)->attach($eventManager, 1000);

                // Listeners that run on every request, just before view is rendered (higher priority numbers run first)
                new ViewVariablesListener($dateService)->attach($eventManager, 1001);
                new LpaViewInjectListener()->attach($eventManager, 1000);
            }
        }
    }

    /**
     * Sets up and starts global sessions.
     *
     * @param MvcEvent $e
     */
    private function bootstrapSession(MvcEvent $e): SessionManager
    {
        $sm = $e->getApplication()->getServiceManager();

        $nativeSession = $sm->get(NativeSessionConfig::class);
        $nativeSession->configure();

        /** @var SessionManager $session */
        $sessionManager = $sm->get('SessionManager');

        // Always starts the session.
        $sessionManager->start();

        // Ensures this SessionManager is used for all Session Containers.
        Container::setDefaultManager($sessionManager);

        $sm->get(SessionManagerSupport::class)->initialise();

        return $sessionManager;
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
        $authenticationService = $sm->get(AuthenticationService::class);
        /** @var Identity $identity */
        $identity = $authenticationService->getIdentity();

        //  If there is an identity (logged in user) then get the token details and check to see if it has expired
        if (!is_null($identity) && $updateToken) {
            try {
                $info = $sm->get('UserService')->getTokenInfo($identity->token());

                if ($info['success'] && !is_null($info['expiresIn'])) {
                    // update the time the token expires in the session
                    $identity->tokenExpiresIn($info['expiresIn']);
                } else {
                    $authenticationService->clearIdentity();

                    // Record that identity was cleared because of a 500 error (normally db-related)
                    if ($info['failureCode'] >= 500) {
                        $sessionUtility = $sm->get(SessionUtility::class);
                        $sessionUtility->setInMvc(ContainerNamespace::AUTH_FAILURE_REASON, 'reason', 'Internal system error');
                        $sessionUtility->setInMvc(ContainerNamespace::AUTH_FAILURE_REASON, 'code', $info['failureCode']);
                    }
                }
            } catch (ApiException $ex) {
                $authenticationService->clearIdentity();
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
                AuthenticationService::class => 'AuthenticationService',
                ServiceLocatorInterface::class => ServiceManager::class,
                IDateService::class => DateService::class,
            ],
            'factories' => [
                'ApiClient'             => 'Application\Model\Service\ApiClient\ClientFactory',
                'AuthenticationService' => 'Application\Model\Service\Authentication\AuthenticationServiceFactory',
                'OrdnanceSurvey'        => 'Application\Model\Service\AddressLookup\OrdnanceSurveyFactory',
                'SessionManager'        => 'Application\Model\Service\Session\SessionFactory',
                'MailTransport'         => 'Application\Model\Service\Mail\Transport\MailTransportFactory',
                'Logger'                => 'MakeShared\Logging\LoggerFactory',
                SessionUtility::class => function () {
                    return new SessionUtility();
                },
                SessionManagerSupport::class => function (ServiceLocatorInterface $sm) {
                    return new SessionManagerSupport($sm->get('SessionManager'), $sm->get(SessionUtility::class));
                },
                SessionMiddleware::class => function () {
                    return new SessionMiddleware(new PhpSessionPersistence());
                },
                'ExporterFactory'       => ReflectionBasedAbstractFactory::class,

                // Authentication Adapter
                'LpaAuthAdapter' => function (ServiceLocatorInterface $sm) {
                    return new LpaAuthAdapter($sm->get('ApiClient'));
                },

                // Creates new container to store additional session information
                'PersistentSessionDetails' => function (ServiceLocatorInterface $sm) {
                    $route = $sm->get('Application')->getMvcEvent()->getRouteMatch();

                    return new PersistentSessionDetails($route, $sm->get(SessionUtility::class));
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
                    $policy = $sm->has(WritePolicy::class) ? $sm->get(WritePolicy::class) : null;

                    $filter = static function () use ($policy) {
                        return $policy === null ? empty($_SERVER['HTTP_X_SESSIONREADONLY']) : $policy->allowsWrite();
                    };

                    return new FilteringSaveHandler($redisClient, [$filter]);
                },


                WritePolicy::class => function (ServiceLocatorInterface $sm) {
                    $request = $sm->has('Request') ? $sm->get('Request') : null;
                    return new WritePolicy($request);
                },

                NativeSessionConfig::class => function (ServiceLocatorInterface $sm) {
                    $settings = $sm->get('config')['session']['native_settings'] ?? [];
                    $handler  = $sm->get('SaveHandler');
                    return new NativeSessionConfig($settings, $handler);
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

                'Calculator' => function () {
                    return new Calculator();
                },
                PingHandler::class => PingHandlerFactory::class,
                PingHandlerJson::class => PingHandlerJsonFactory::class,
                PingHandlerPingdom::class => PingHandlerPingdomFactory::class,
                FormLinkedErrors::class => fn () => new FormLinkedErrors(),
                AppFiltersExtension::class => function (ServiceLocatorInterface $sm) {
                    return new AppFiltersExtension($sm->get('config'));
                },
                AppFunctionsExtension::class => function (ServiceLocatorInterface $sm) {
                    return new AppFunctionsExtension(
                        $sm->get('config'),
                        $sm->get(FormLinkedErrors::class),
                        $sm->get(TemplateRendererInterface::class),
                        $sm->get(SystemMessage::class),
                        $sm->get(AccordionService::class),
                        $sm->get(NavigationViewModelHelper::class),
                    );
                },
                LoggerInterface::class => LoggerFactory::class,
                CookiesHandler::class     => CookiesHandlerFactory::class,
                DateService::class           => InvokableFactory::class,
                FeedbackHandler::class       => FeedbackHandlerFactory::class,
                FeedbackThanksHandler::class => FeedbackThanksHandlerFactory::class,
                SystemMessage::class => SystemMessageFactory::class,
                ContinuationSheets::class => InvokableFactory::class,
                GuidanceHandler::class      => GuidanceHandlerFactory::class,
                AccordionService::class      => AccordionServiceFactory::class,
                NavigationViewModelHelper::class      => NavigationViewModelHelperFactory::class,
                EnableCookieHandler::class => fn (ServiceLocatorInterface $sm) => new EnableCookieHandler(
                    $sm->get(TemplateRendererInterface::class),
                ),

                TermsHandler::class => fn (ServiceLocatorInterface $sm) => new TermsHandler(
                    $sm->get(TemplateRendererInterface::class),
                ),

                AccessibilityHandler::class => fn (ServiceLocatorInterface $sm) => new AccessibilityHandler(
                    $sm->get(TemplateRendererInterface::class),
                ),

                PrivacyHandler::class => fn (ServiceLocatorInterface $sm) => new PrivacyHandler(
                    $sm->get(TemplateRendererInterface::class),
                ),

                ContactHandler::class => fn (ServiceLocatorInterface $sm) => new ContactHandler(
                    $sm->get(TemplateRendererInterface::class),
                ),

                HomeRedirectHandler::class => HomeRedirectHandlerFactory::class,
                HomeHandler::class => HomeHandlerFactory::class,
                AboutYouHandler::class => AboutYouHandlerFactory::class,
                AuthenticationListener::class => function (ServiceLocatorInterface $sm) {
                    return new AuthenticationListener(
                        $sm->get(SessionUtility::class),
                        $sm->get(AuthenticationService::class),
                        null  // No UrlHelper for MVC
                    );
                },

                UserDetailsListener::class => function (ServiceLocatorInterface $sm) {
                    return new UserDetailsListener(
                        $sm->get(SessionUtility::class),
                        $sm->get(Details::class),
                        $sm->get(AuthenticationService::class),
                        $sm->get('SessionManager'),
                        $sm->get(LoggerInterface::class),
                    );
                },

                TermsAndConditionsListener::class => function (ServiceLocatorInterface $sm) {
                    return new TermsAndConditionsListener(
                        $sm->get('config'),
                        $sm->get(SessionUtility::class),
                        $sm->get(AuthenticationService::class),
                        null  // No UrlHelper for MVC
                    );
                },
                RegisterHandler::class => RegisterHandlerFactory::class,
                ResendActivationEmailHandler::class => ResendActivationEmailHandlerFactory::class,
                ConfirmRegistrationHandler::class => ConfirmRegistrationHandlerFactory::class,
                ChangeEmailAddressHandler::class => ChangeEmailAddressHandlerFactory::class,
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
