<?php

use Application\Handler;
use Laminas\Di\Container\ServiceManager\AutowireFactory;
use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\ServiceManager\ServiceLocatorInterface;
use MakeShared\Factories\ListenerAbstractFactory;
use MakeShared\Handler\PingHandlerElb;
use MakeShared\Logging\LoggerFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

return [

    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Laminas\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ],
                ],
            ],

            'ping' => [
                'type' => 'Laminas\Router\Http\Segment',
                'options' => [
                    'route' => '/ping',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\PingHandler::class,
                    ],
                ],
            ],

            'elb-ping' => [
                'type' => 'Laminas\Router\Http\Segment',
                'options' => [
                    'route' => '/ping/elb',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => PingHandlerElb::class,
                    ],
                ],
            ],

            'stats' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/stats/:type',
                    'constraints' => [
                        'type' => '[a-z0-9][a-z0-9-]*',
                    ],
                    'defaults' => [
                        'controller' => 'Application\Controller\Stats',
                    ],
                ],
            ],

            'feedback' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/user-feedback',
                    'defaults' => [
                        'controller' => 'Application\Controller\Feedback',
                    ],
                ],
            ],

            'auth-routes' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v2',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller\Version2\Auth',
                    ],
                ],
                'child_routes' => [

                    'authenticate' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/authenticate',
                            'defaults' => [
                                'controller' => 'AuthenticateController',
                                'action'     => 'authenticate',
                            ],
                        ],
                    ],
                    'session-expiry' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/session-expiry',
                            'defaults' => [
                                'controller' => 'AuthenticateController',
                                'action'     => 'sessionExpiry',
                            ],
                        ],
                    ],
                    'session-set-expiry' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/session-set-expiry',
                            'defaults' => [
                                'controller' => 'AuthenticateController',
                                'action'     => 'setSessionExpiry',
                            ],
                        ],
                    ],

                    'onelogin-start' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/auth/onelogin/start',
                            'defaults' => [
                                'controller' => 'OneLoginController',
                                'action'     => 'start',
                            ],
                        ],
                    ],

                    'shared-space' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/shared-space',
                            'defaults' => [
                                'controller' => 'SharedSpaceController',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [

                            'create' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/create',
                                    'defaults' => [
                                        'action' => 'create',
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'users' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/users',
                            'defaults' => [
                                'controller' => 'UsersController',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [

                            'search-users' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/search',
                                    'defaults' => [
                                        'action' => 'search',
                                    ],
                                ],
                            ],
                            'match-users' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/match',
                                    'defaults' => [
                                        'action' => 'match',
                                    ],
                                ],
                            ],
                            'email-change' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/:userId/email',
                                    'constraints' => [
                                        'userId'  => '[a-zA-Z0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'EmailController',
                                        'action'     => 'change',
                                    ],
                                ],
                            ],
                            'email-verify' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/email',
                                    'defaults' => [
                                        'controller' => 'EmailController',
                                        'action'     => 'verify',
                                    ],
                                ],
                            ],
                            'password-change' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:userId]/password',
                                    'constraints' => [
                                        'userId' => '[a-zA-Z0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'PasswordController',
                                        'action'     => 'change',
                                    ],
                                ],
                            ],
                            'password-reset' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/password-reset',
                                    'defaults' => [
                                        'controller' => 'PasswordController',
                                        'action'     => 'reset',
                                    ],
                                ],
                            ],

                        ],
                    ],

                ],
            ],

            'api-routes' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v2',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller\Version2\Lpa',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'user' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'       => '/user/:userId',
                            'constraints' => [
                                'userId'  => '[a-f0-9]+',
                            ],
                            'defaults' => [
                                'controller' => 'UserController',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'statuses' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/statuses/:lpaIds',
                                    'constraints' => [
                                        'lpaIds' => '[0-9,]+',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => '',
                                        'controller' => Application\Controller\StatusController::class,
                                    ],
                                ],
                            ],

                            'applications' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/applications[/:lpaId]',
                                    'constraints' => [
                                        'lpaId'   => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'ApplicationController',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [

                                    'certificate-provider' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/certificate-provider',
                                            'defaults' => [
                                                'controller' => 'CertificateProviderController',
                                            ],
                                        ],
                                    ],
                                    'correspondent' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/correspondent',
                                            'defaults' => [
                                                'controller' => 'CorrespondentController',
                                            ],
                                        ],
                                    ],
                                    'donor' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/donor',
                                            'defaults' => [
                                                'controller' => 'DonorController',
                                            ],
                                        ],
                                    ],
                                    'instruction' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/instruction',
                                            'defaults' => [
                                                'controller' => 'InstructionController',
                                            ],
                                        ],
                                    ],
                                    'lock' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/lock',
                                            'defaults' => [
                                                'controller' => 'LockController',
                                            ],
                                        ],
                                    ],
                                    'notified-people' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/notified-people[/:notifiedPersonId]',
                                            'constraints' => [
                                                'notifiedPersonId' => '[0-9]+',
                                            ],
                                            'defaults' => [
                                                'controller' => 'NotifiedPeopleController',
                                            ],
                                        ],
                                    ],
                                    'payment' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/payment',
                                            'defaults' => [
                                                'controller' => 'PaymentController',
                                            ],
                                        ],
                                    ],
                                    'pdfs' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/pdfs/:pdfType',
                                            'constraints' => [
                                                'pdfType' => '[a-z0-9][a-z0-9.]*',
                                            ],
                                            'defaults' => [
                                                'controller' => 'PdfController',
                                            ],
                                        ],
                                    ],
                                    'preference' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/preference',
                                            'defaults' => [
                                                'controller' => 'PreferenceController',
                                            ],
                                        ],
                                    ],
                                    'primary-attorneys' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/primary-attorneys[/:primaryAttorneyId]',
                                            'constraints' => [
                                                'primaryAttorneyId' => '[0-9]+',
                                            ],
                                            'defaults' => [
                                                'controller' => 'PrimaryAttorneyController',
                                            ],
                                        ],
                                    ],
                                    'primary-attorney-decisions' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/primary-attorney-decisions',
                                            'defaults' => [
                                                'controller' => 'PrimaryAttorneyDecisionsController',
                                            ],
                                        ],
                                    ],
                                    'repeat-case-number' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/repeat-case-number',
                                            'defaults' => [
                                                'controller' => 'RepeatCaseNumberController',
                                            ],
                                        ],
                                    ],
                                    'replacement-attorneys' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/replacement-attorneys[/:replacementAttorneyId]',
                                            'constraints' => [
                                                'replacementAttorneyId' => '[0-9]+',
                                            ],
                                            'defaults' => [
                                                'controller' => 'ReplacementAttorneyController',
                                            ],
                                        ],
                                    ],
                                    'replacement-attorney-decisions' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/replacement-attorney-decisions',
                                            'defaults' => [
                                                'controller' => 'ReplacementAttorneyDecisionsController',
                                            ],
                                        ],
                                    ],
                                    'seed' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/seed',
                                            'defaults' => [
                                                'controller' => 'SeedController',
                                            ],
                                        ],
                                    ],
                                    'type' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/type',
                                            'defaults' => [
                                                'controller' => 'TypeController',
                                            ],
                                        ],
                                    ],
                                    'who-are-you' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/who-are-you',
                                            'defaults' => [
                                                'controller' => 'WhoAreYouController',
                                            ],
                                        ],
                                    ],
                                    'who-is-registering' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/who-is-registering',
                                            'defaults' => [
                                                'controller' => 'WhoIsRegisteringController',
                                            ],
                                        ],
                                    ],
                                ],
                            ],

                        ],
                    ],

                ],
            ],
        ],
    ],

    'controllers' => [
        'aliases' => [
            //  The route configuration uses these short names; alias them to the
            //  real classes so they can be autowired.
            'Application\Controller\Stats' => Application\Controller\StatsController::class,
            'Application\Controller\Feedback' => Application\Controller\FeedbackController::class,
        ],
        'invokables' => [
            'Application\Controller\Index' => 'Application\Controller\IndexController'
        ],
        'factories' => [
            Application\Controller\StatusController::class =>
                Application\ControllerFactory\StatusControllerFactory::class
        ],
        'abstract_factories' => [
            'Application\ControllerFactory\AuthControllerAbstractFactory',
            'Application\ControllerFactory\LpaControllerAbstractFactory',
            AutowireFactory::class,
        ],
    ], // controllers

    'service_manager' => [
        'abstract_factories' => [
            ListenerAbstractFactory::class,
            'Application\Model\Service\ServiceAbstractFactory',
            'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
            'Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory',
        ],
        'factories' => [
            'Application\Command\GenerateStatsCommand' => 'Application\Command\GenerateStatsCommand',
            'Application\Command\AccountCleanupCommand' => 'Application\Command\AccountCleanupCommand',
            'Application\Command\LockCommand' => 'Application\Command\LockCommand',
            LoggerInterface::class => LoggerFactory::class,
            Application\Model\Service\OneLogin\DiscoveryDocumentFetcher::class => static function (ServiceLocatorInterface $container): Application\Model\Service\OneLogin\DiscoveryDocumentFetcher {
                $config = $container->get('config');
                return new Application\Model\Service\OneLogin\DiscoveryDocumentFetcher(
                    $container->get(GuzzleHttp\Client::class),
                    $config['onelogin']['discovery_url'] ?? '',
                );
            },
        ],
        'initializers' => [
            function (ServiceLocatorInterface $container, $instance) {
                if ($instance instanceof LoggerAwareInterface) {
                    $instance->setLogger($container->get(LoggerInterface::class));
                }
            },
        ]
    ], // service_manager

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],

    'laminas-cli' => [
        'commands' => [
            'service-api:generate-stats' => Application\Command\GenerateStatsCommand::class,
            'service-api:account-cleanup' => Application\Command\AccountCleanupCommand::class,
            'service-api:lock' => Application\Command\LockCommand::class,
        ],
    ],

];
