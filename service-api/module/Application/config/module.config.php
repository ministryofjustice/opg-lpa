<?php

use Application\Handler;
use Application\Model\Service\OneLogin as OneLoginService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzlePsr18;
use Laminas\Di\Container\ServiceManager\AutowireFactory;
use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\ServiceManager\ServiceLocatorInterface;
use MakeShared\Factories\ListenerAbstractFactory;
use MakeShared\Handler\PingHandlerElb;
use MakeShared\Logging\LoggerFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

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
                            'lpas' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/lpas',
                                    'defaults' => [
                                        'action' => 'lpas',
                                    ],
                                ],
                            ],
                            'members' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route' => '/members',
                                ],
                                'may_terminate' => false,
                                'child_routes' => [
                                    'add' => [
                                        'type'    => 'Method',
                                        'options' => [
                                            'verb'     => 'post',
                                            'defaults' => [
                                                'action' => 'addMember',
                                            ],
                                        ],
                                    ],
                                    'update' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/:memberUserId',
                                            'constraints' => [
                                                'memberUserId' => '[a-zA-Z0-9]+',
                                            ],
                                        ],
                                        'may_terminate' => false,
                                        'child_routes'  => [
                                            'get' => [
                                                'type'    => 'Method',
                                                'options' => [
                                                    'verb'     => 'get',
                                                    'defaults' => [
                                                        'action' => 'member',
                                                    ],
                                                ],
                                            ],
                                            'patch' => [
                                                'type'    => 'Method',
                                                'options' => [
                                                    'verb'     => 'patch',
                                                    'defaults' => [
                                                        'action' => 'updateMember',
                                                    ],
                                                ],
                                            ],
                                            'delete' => [
                                                'type'    => 'Method',
                                                'options' => [
                                                    'verb'     => 'delete',
                                                    'defaults' => [
                                                        'action' => 'deleteMember',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'members-and-invites' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/members-and-invites',
                                    'defaults' => [
                                        'action' => 'membersAndInvites',
                                    ],
                                ],
                            ],
                            'invite' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/invite',
                                    'defaults' => [
                                        'action' => 'invite',
                                    ],
                                ],
                            ],
                            'revoke-invite' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/revoke-invite/:memberInviteId',
                                    'constraints' => [
                                        'memberInviteId' => '[0-9]+',
                                    ],
                                ],
                                'may_terminate' => false,
                                'child_routes'  => [
                                    'post' => [
                                        'type'    => 'Method',
                                        'options' => [
                                            'verb'     => 'post',
                                            'defaults' => [
                                                'action' => 'revokeInvite',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'join' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/join',
                                    'defaults' => [
                                        'action' => 'join',
                                    ],
                                ],
                            ],
                            'import' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/import',
                                ],
                                'child_routes'  => [
                                    'post' => [
                                        'type'    => 'Method',
                                        'options' => [
                                            'verb'     => 'post',
                                            'defaults' => [
                                                'action' => 'import',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'onelogin-callback' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/auth/onelogin/callback',
                            'defaults' => [
                                'controller' => 'OneLoginController',
                                'action'     => 'callback',
                            ],
                        ],
                    ],

                    'onelogin-link' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/auth/onelogin/link',
                            'defaults' => [
                                'controller' => 'OneLoginController',
                                'action'     => 'link',
                            ],
                        ],
                    ],

                    'onelogin-backchannel-logout' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/auth/onelogin/backchannel-logout',
                            'defaults' => [
                                'controller' => 'OneLoginController',
                                'action'     => 'backChannelLogout',
                            ],
                        ],
                    ],

                    'onelogin-create' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/auth/onelogin/create',
                            'defaults' => [
                                'controller' => 'OneLoginController',
                                'action'     => 'create',
                            ],
                        ],
                    ],

                    'admin' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/admin',
                            'defaults' => [
                                'controller' => 'AdminController',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'search-users' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/search-users',
                                    'defaults' => [
                                        'action' => 'searchUsers',
                                    ],
                                ],
                            ],

                            'match-users' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/match-users',
                                    'defaults' => [
                                        'action' => 'matchUsers',
                                    ],
                                ],
                            ],

                            'shared-space-lpas' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/shared-space/:sharedSpaceId/lpas',
                                    'constraints' => [
                                        'sharedSpaceId'  => '[a-zA-Z0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'sharedSpaceLpas',
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
                                    'instruction-preference' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'       => '/instruction-preference',
                                            'defaults' => [
                                                'controller' => 'InstructionPreferenceController',
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
                Application\ControllerFactory\StatusControllerFactory::class,
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
            'Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory',
        ],
        'factories' => [
            'Application\Command\GenerateStatsCommand' => 'Application\Command\GenerateStatsCommand',
            'Application\Command\AccountCleanupCommand' => 'Application\Command\AccountCleanupCommand',
            'Application\Command\LockCommand' => 'Application\Command\LockCommand',
            LoggerInterface::class => LoggerFactory::class,
            'OneLoginPsr16Cache' => static function (): Psr16Cache {
                if (ApcuAdapter::isSupported()) {
                    return new Psr16Cache(new ApcuAdapter('onelogin'));
                }

                return new Psr16Cache(new ArrayAdapter());
            },

            OneLoginService\KeyPairManager::class => static function (ServiceLocatorInterface $container): OneLoginService\KeyPairManager {
                $config = $container->get('config');

                $privateKey = $config['onelogin']['private_key'] ?? null;
                $keyId      = $config['onelogin']['key_id'] ?? null;

                if (!is_string($privateKey) || $privateKey === '') {
                    throw new \RuntimeException('Missing required config: onelogin.private_key');
                }

                if (!is_string($keyId) || $keyId === '') {
                    throw new \RuntimeException('Missing required config: onelogin.key_id');
                }

                return new OneLoginService\KeyPairManager($privateKey, $keyId);
            },

            OneLoginService\AuthorisationClientManager::class => static function (ServiceLocatorInterface $container): OneLoginService\AuthorisationClientManager {
                $config = $container->get('config');

                $clientId     = $config['onelogin']['client_id'] ?? null;
                $discoveryUrl = $config['onelogin']['discovery_url'] ?? null;

                if (!is_string($clientId) || $clientId === '') {
                    throw new \RuntimeException('Missing required config: onelogin.client_id');
                }

                if (!is_string($discoveryUrl) || $discoveryUrl === '') {
                    throw new \RuntimeException('Missing required config: onelogin.discovery_url');
                }

                return new OneLoginService\AuthorisationClientManager(
                    $clientId,
                    $discoveryUrl,
                    $container->get(OneLoginService\KeyPairManager::class),
                    new GuzzlePsr18(new GuzzleClient()),
                    $container->get('OneLoginPsr16Cache'),
                );
            },

            OneLoginService\LogoutTokenVerifier::class => static function (
                ServiceLocatorInterface $container
            ): OneLoginService\LogoutTokenVerifier {
                return new OneLoginService\LogoutTokenVerifier(
                    $container->get(OneLoginService\AuthorisationClientManager::class)
                );
            },

            OneLoginService\FacileAuthorizationServiceAdapter::class => static function (): OneLoginService\FacileAuthorizationServiceAdapter {
                $httpClient = new GuzzlePsr18(new GuzzleClient());

                $authBuilder = new AuthorizationServiceBuilder();
                $authBuilder->setHttpClient($httpClient);

                $userInfoBuilder = new UserInfoServiceBuilder();
                $userInfoBuilder->setHttpClient($httpClient);

                return new OneLoginService\FacileAuthorizationServiceAdapter(
                    $authBuilder->build(),
                    $userInfoBuilder->build(),
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
