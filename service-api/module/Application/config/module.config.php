<?php

return [

    'router' => [
        'routes' => [

            'home' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ],
                ],
            ], // home

            'application' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/application',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                            ],
                        ],
                    ],
                ],
            ], // Services

            'api-v1' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v1/user/:userId',
                    'constraints' => [
                        'userId' => '[a-f][a-f0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller\Version1',
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
                                'resourceId' => '[0-9]+',
                                'resource'   => '[a-z][a-z]*',
                            ],
                            'defaults' => [
                                'controller'    => 'Rest',
                            ],
                        ],
                    ], // level-2

                ], // child_routes

            ], // api-v1

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
                ],
                'user' => [
                    // An authenticated request.
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
            'Application\Controller\Version1\Rest' => 'Application\Controller\Version1\RestController',
        ],
        'factories' => [
            //'Application\Controller\Version1\Rest' => 'Application\Factory\RestControllerFactory',
        ],
    ], // controllers


    'service_manager' => [
        'initializers' => [
            'ZfcRbac\Initializer\AuthorizationServiceInitializer',
        ],
        'invokables' => [
            'resource-users'                    => 'Application\Model\Rest\Users\Resource',
            'resource-applications'             => 'Application\Model\Rest\Applications\Resource',
            'resource-status'                   => 'Application\Model\Rest\Status\Resource',
            'resource-type'                     => 'Application\Model\Rest\Type\Resource',
            'resource-instruction'              => 'Application\Model\Rest\Instruction\Resource',
            'resource-preference'               => 'Application\Model\Rest\Preference\Resource',
            'resource-how-decisions-are-made'   => 'Application\Model\Rest\xxx\Resource',
            'resource-donor'                    => 'Application\Model\Rest\Donor\Resource',
            'resource-correspondent'            => 'Application\Model\Rest\xxx\Resource',
            'resource-payment'                  => 'Application\Model\Rest\xxx\Resource',
            'resource-who-is-registering'       => 'Application\Model\Rest\xxx\Resource',
            'resource-who-are-you'              => 'Application\Model\Rest\xxx\Resource',
            'resource-lock'                     => 'Application\Model\Rest\xxx\Resource',
            'resource-seed'                     => 'Application\Model\Rest\xxx\Resource',
            'resource-attorneys'                => 'Application\Model\Rest\xxx\Resource',
            'resource-certificate-providers'    => 'Application\Model\Rest\xxx\Resource',
            'resource-notified-people'          => 'Application\Model\Rest\xxx\Resource',
            'resource-pdfs'                     => 'Application\Model\Rest\Pdf\Resource',
        ],
        'abstract_factories' => [
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
        'aliases' => [
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
            ],
        ],
    ],

];
