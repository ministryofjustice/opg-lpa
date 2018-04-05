<?php

return [

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
            ], // home

            'ping' => [
                'type' => 'Zend\Router\Http\Segment',
                'options' => [
                    'route' => '/ping[/:action]',
                    'defaults' => [
                        'controller' => 'Application\Controller\Ping',
                        'action'     => 'index',
                    ],
                ],
            ], // ping

            'api-v1' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v1',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller\Version1',
                        'controller'    => 'Rest',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [

                    'stats' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/stats/:type',
                            'constraints' => [
                                'userId' => '[a-f0-9]+',
                                'type' => '[a-z0-9][a-z0-9-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'Rest',
                                'resource'      => 'stats'
                            ],
                        ],
                    ], // stats

                    'user' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/users/:userId',
                            'constraints' => [
                                'userId' => '[a-f0-9]+',
                            ],
                            'defaults' => [
                                'controller'    => 'Rest',
                                'resource'      => 'users'
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [

                            'level-1' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/applications[/:lpaId]',
                                    'constraints' => [
                                        'lpaId'     => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller'    => 'Rest',
                                        'resource'      => 'applications'
                                    ],
                                ],
                            ], // level-1

                            'level-2' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/applications/:lpaId/:resource[/:resourceId]',
                                    'constraints' => [
                                        'lpaId'      => '[0-9]+',
                                        'resource'   => '[a-z][a-z-]*',
                                        'resourceId' => '[a-z0-9][a-z0-9.]*',
                                    ],
                                    'defaults' => [
                                        'controller'    => 'Rest',
                                    ],
                                ],
                            ], // level-2

                        ], // child_routes

                    ], // user

                ], // child_routes

            ], // api-v1

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

                    //  TODO - Is this used yet?
                    'stats' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/stats/:type',
                            'constraints' => [
                                'userId' => '[a-f0-9]+',
                                'type' => '[a-z0-9][a-z0-9-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'Stats',
                                'resource'      => 'stats'
                            ],
                        ],
                    ], // stats

                    'user' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'       => '/users/:userId',
                            'constraints' => [
                                'userId'  => '[a-f0-9]+',
                            ],
                            'defaults' => [
                                'controller' => 'User',
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
                                        'controller' => 'Application',
                                    ],
                                ],
                            ], // applications

                        ], // child_routes

                    ], // user

                ], // child_routes

            ], // api-v2

        ], //routes

    ], // router

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
            'Application\Controller\Index'          => 'Application\Controller\IndexController',
            'Application\Controller\Version1\Rest'  => 'Application\Controller\Version1\RestController',
        ],
        'factories' => [
            'Application\Controller\Console\GenerateStats'  => 'Application\Controller\Console\GenerateStatsControllerFactory',
            'Application\Controller\Ping'                   => 'Application\Controller\PingControllerFactory',
            'Application\Controller\Version2\Application'   => 'Application\Controller\Version2\ApplicationControllerFactory',
            'Application\Controller\Version2\User'          => 'Application\Controller\Version2\UserControllerFactory',
        ],
    ], // controllers


    'service_manager' => [
        'initializers' => [
            'ZfcRbac\Initializer\AuthorizationServiceInitializer',
        ],
        'factories' => [
            'StatsService' => 'Application\Model\Service\System\StatsFactory',
            \Application\DataAccess\UserDal::class => \Application\DataAccess\UserDalFactory::class,
        ],
        'abstract_factories' => [
            'Application\Model\Rest\ResourceAbstractFactory',
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
        ],
        'aliases' => [
            'resource-users'                            => 'Application\Model\Rest\Users\Resource',
            'resource-applications'                     => 'Application\Model\Rest\Applications\Resource',
            'resource-status'                           => 'Application\Model\Rest\Status\Resource',
            'resource-type'                             => 'Application\Model\Rest\Type\Resource',
            'resource-instruction'                      => 'Application\Model\Rest\Instruction\Resource',
            'resource-preference'                       => 'Application\Model\Rest\Preference\Resource',
            'resource-primary-attorney-decisions'       => 'Application\Model\Rest\AttorneyDecisionsPrimary\Resource',
            'resource-replacement-attorney-decisions'   => 'Application\Model\Rest\AttorneyDecisionsReplacement\Resource',
            'resource-donor'                            => 'Application\Model\Rest\Donor\Resource',
            'resource-correspondent'                    => 'Application\Model\Rest\Correspondent\Resource',
            'resource-payment'                          => 'Application\Model\Rest\Payment\Resource',
            'resource-who-is-registering'               => 'Application\Model\Rest\WhoIsRegistering\Resource',
            'resource-who-are-you'                      => 'Application\Model\Rest\WhoAreYou\Resource',
            'resource-certificate-provider'             => 'Application\Model\Rest\CertificateProvider\Resource',
            'resource-lock'                             => 'Application\Model\Rest\Lock\Resource',
            'resource-seed'                             => 'Application\Model\Rest\Seed\Resource',
            'resource-primary-attorneys'                => 'Application\Model\Rest\AttorneysPrimary\Resource',
            'resource-replacement-attorneys'            => 'Application\Model\Rest\AttorneysReplacement\Resource',
            'resource-notified-people'                  => 'Application\Model\Rest\NotifiedPeople\Resource',
            'resource-repeat-case-number'               => 'Application\Model\Rest\RepeatCaseNumber\Resource',
            'resource-pdfs'                             => 'Application\Model\Rest\Pdfs\Resource',
            'resource-metadata'                         => 'Application\Model\Rest\Metadata\Resource',
            'resource-stats'                            => 'Application\Model\Rest\Stats\Resource',

            'translator' => 'MvcTranslator',
            'AuthenticationService' => 'Zend\Authentication\AuthenticationService',
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

    // Placeholder for console routes
    'console' => [
        'router' => [
            'routes' => [

                'account-cleanup' => [
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

];
