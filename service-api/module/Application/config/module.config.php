<?php

return [

    'console' => [
        'router' => [
            'routes' => [
                'account-cleanup' => [
                    'type'    => 'simple',
                    'options' => [
                        'route'    => 'account-cleanup',
                        'defaults' => [
                            'controller' => 'Application\Controller\Console\AccountCleanup',
                            'action'     => 'cleanup'
                        ],
                    ],
                ],
                'generate-stats' => [
                    'type'    => 'simple',
                    'options' => [
                        'route'    => 'generate-stats',
                        'defaults' => [
                            'controller' => 'Application\Controller\Console\GenerateStats',
                            'action'     => 'generate'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ],
                ],
            ],

            'ping' => [
                'type' => 'Zend\Router\Http\Segment',
                'options' => [
                    'route' => '/ping[/:action]',
                    'defaults' => [
                        'controller' => 'Application\Controller\Ping',
                        'action'     => 'index',
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

            'api-v2' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v2',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller\Version2',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [

                    'user' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'       => '/users/:userId',
                            'constraints' => [
                                'userId'  => '[a-f0-9]+',
                            ],
                            'defaults' => [
                                'controller' => 'UserController',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [

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

            'auth-v1' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v1',
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
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [

                            'post' => [
                                'type' => 'method',
                                'options' => [
                                    'verb' => 'post',
                                    'defaults' => [
                                        'action' => 'index'
                                    ],
                                ],
                            ],

                        ],
                    ], // authenticate

                    'token' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/token/:token',
                            'constraints' => [
                                'token' => '[a-zA-Z0-9]+',
                            ],
                            'defaults' => [
                                'controller' => 'AuthenticateController',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [

                            'delete' => [
                                'type' => 'method',
                                'options' => [
                                    'verb' => 'delete',
                                    'defaults' => [
                                        'action' => 'delete'
                                    ],
                                ],
                            ], // delete

                        ],
                    ], // token


                    'user' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/users',
                            'defaults' => [
                                'controller' => 'UsersController',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [

                            'create' => [
                                'type' => 'method',
                                'options' => [
                                    'verb' => 'post',
                                    'defaults' => [
                                        'controller' => 'RegistrationController',
                                        'action' => 'create'
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'activate' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/activate',
                                            'defaults' => [
                                                'controller' => 'RegistrationController',
                                                'action'    => 'activate',
                                            ],
                                        ],
                                    ], // activate
                                ]
                            ], // create

                            'password-reset' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/password-reset',
                                    'defaults' => [
                                        'controller' => 'PasswordController',
                                        'action'    => 'passwordReset',
                                    ],
                                ],
                            ], // password-reset

                            'password-reset-update' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/password-reset-update',
                                    'defaults' => [
                                        'controller' => 'PasswordController',
                                        'action'    => 'passwordResetUpdate',
                                    ],
                                ],
                            ], // password-reset-update

                            'confirm-new-email' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/confirm-new-email',
                                    'defaults' => [
                                        'controller' => 'EmailController',
                                        'action'    => 'update-email',
                                    ],
                                ],
                            ], // confirm-new-email

                            //---

                            'id' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/:userId',
                                    'constraints' => [
                                        'userId' => '[a-zA-Z0-9]+',
                                    ],
                                ],
                                'may_terminate' => false,
                                'child_routes' => [

                                    'get' => [
                                        'type' => 'method',
                                        'options' => [
                                            'verb' => 'get',
                                            'defaults' => [
                                                'action' => 'index'
                                            ],
                                        ],
                                    ], // get

                                    'email' => [
                                        'type'    => 'segment',
                                        'options' => [
                                            'route'    => '/email',
                                            'defaults' => [
                                                'controller' => 'EmailController',
                                            ],
                                        ],
                                        'may_terminate' => false,
                                        'child_routes' => [
                                            'request-token' => [
                                                'type' => 'segment',
                                                'options' => [
                                                    'route' => '/:newEmail',
                                                    'defaults' => [
                                                        'action'    => 'get-email-update-token',
                                                    ],
                                                ],
                                            ], // request-token
                                            'update-email' => [
                                                'type' => 'method',
                                                'options' => [
                                                    'verb' => 'post',
                                                    'defaults' => [
                                                        'action'    => 'update-email',
                                                    ],
                                                ],
                                            ], // update-email
                                        ]
                                    ],

                                    'password' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/password',
                                            'defaults' => [
                                                'controller' => 'PasswordController',
                                                'action' => 'change',
                                            ],
                                        ],
                                    ], // password

                                ],
                            ], // id

                            'search-users' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/search',
                                    'defaults' => [
                                        'action'    => 'search',
                                    ],
                                ],
                            ], // search-users

                        ], // child_routes

                    ], // user

                ], // child_routes

            ], // v1

        ],
    ],

    'zfc_rbac' => [
        'assertion_map' => [
            'isAuthorizedToManageUser' => 'Application\Library\Authorization\Assertions\IsAuthorizedToManageUser',
        ],
        'role_provider' => [
            'ZfcRbac\Role\InMemoryRoleProvider' => [
                'admin' => [
                    // An authenticated request with admin rights.
                    'children' => ['user'],
                    'permissions' => [ 'admin' ]
                ],
                'user' => [
                    // An authenticated request.
                    'children' => ['guest'],
                    'permissions' => [ 'authenticated', 'isAuthorizedToManageUser' ]
                ],
                'service' => [
                    // An authenticated request from a service (e.g. auth service)
                    'children' => ['guest'],
                    'permissions' => [ 'authenticated', 'isAuthorizedToManageUser' ]
                ],
                'guest' => [
                    // An unauthenticated request.
                    'permissions' => ['stats']
                ],
            ],
        ],
    ], // zfc_rbac

    'controllers' => [
        'invokables' => [
            'Application\Controller\Index' => 'Application\Controller\IndexController',
        ],
        'factories' => [
            'Application\Controller\Console\AccountCleanup' => 'Application\Controller\Console\AccountCleanupControllerFactory',
            'Application\Controller\Console\GenerateStats'  => 'Application\Controller\Console\GenerateStatsControllerFactory',
            'Application\Controller\Ping'                   => 'Application\Controller\PingControllerFactory',
            'Application\Controller\Stats'                  => 'Application\Controller\StatsControllerFactory',
        ],
        'abstract_factories' => [
            'Application\Controller\Version2\Auth\ControllerAbstractFactory',
            'Application\Controller\ControllerAbstractFactory',
        ],
    ], // controllers


    'service_manager' => [
        'factories' => [
            Application\Model\Service\AccountCleanup\Service::class => Application\Model\Service\AccountCleanup\ServiceFactory::class,
            Application\Model\Service\System\Stats::class           => Application\Model\Service\System\StatsFactory::class,
            Application\Model\Service\Stats\Service::class          => Application\Model\Service\Stats\ServiceFactory::class,
        ],
        'abstract_factories' => [
            'Application\Model\Service\ServiceAbstractFactory',
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
        ],
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
    ], // service_manager

    'translator' => [
        'locale' => 'en_US',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],

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

];
