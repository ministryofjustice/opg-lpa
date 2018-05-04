<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'Auth\Controller\Index',
                        'action'     => 'index',
                    ],
                ],
            ],

            'ping' => [
                'type' => 'Zend\Router\Http\Segment',
                'options' => [
                    'route' => '/ping[/:action]',
                    'defaults' => [
                        'controller' => 'Auth\Controller\Ping',
                        'action'     => 'index',
                    ],
                ],
            ], // ping

            'v1' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v1',
                    'defaults' => [
                        '__NAMESPACE__' => 'Auth\Controller\Version1',
                        'controller'    => 'Rest',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [

                    'stats' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/stats',
                            'defaults' => [
                                'controller' => 'Stats',
                                'action' => 'index',
                            ],
                        ],
                    ], // stats

                    'authenticate' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/authenticate',
                            'defaults' => [
                                'controller'    => 'Authenticate',
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
                                'controller'    => 'Authenticate',
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
                                'controller'    => 'User',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [

                            'create' => [
                                'type' => 'method',
                                'options' => [
                                    'verb' => 'post',
                                    'defaults' => [
                                        'controller' => 'Registration',
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
                                                'controller' => 'Registration',
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
                                        'controller' => 'Password',
                                        'action'    => 'passwordReset',
                                    ],
                                ],
                            ], // password-reset

                            'password-reset-update' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/password-reset-update',
                                    'defaults' => [
                                        'controller' => 'Password',
                                        'action'    => 'passwordResetUpdate',
                                    ],
                                ],
                            ], // password-reset-update

                            'confirm-new-email' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/confirm-new-email',
                                    'defaults' => [
                                        'controller' => 'Email',
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

                                    'delete' => [
                                        'type' => 'method',
                                        'options' => [
                                            'verb' => 'delete',
                                            'defaults' => [
                                                'action' => 'delete'
                                            ],
                                        ],
                                    ], // delete

                                    'email' => [
                                        'type'    => 'segment',
                                        'options' => [
                                            'route'    => '/email',
                                            'defaults' => [
                                                'controller' => 'Email',
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
                                                'controller' => 'Password',
                                                'action'    => 'change',
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

    'controllers' => [
        'abstract_factories' => [
            'Auth\Controller\Version1\AuthenticatedControllerAbstractFactory',
        ],
        'factories' => [
            'Auth\Controller\Console\AccountCleanup' => 'Auth\Controller\Console\AccountCleanupControllerFactory',
            'Auth\Controller\Version1\Registration' => 'Auth\Controller\Version1\RegistrationControllerFactory',
            'Auth\Controller\Version1\Stats' => 'Auth\Controller\Version1\StatsControllerFactory',
            'Auth\Controller\Ping' => 'Auth\Controller\PingControllerFactory',
        ],
        'invokables' => [
            'Auth\Controller\Index' => 'Auth\Controller\IndexController',
        ],
        'aliases' => [
            'Auth\Controller\Version1\Authenticate' => 'Auth\Controller\Version1\AuthenticateController',
            'Auth\Controller\Version1\Email' => 'Auth\Controller\Version1\EmailController',
            'Auth\Controller\Version1\Password' => 'Auth\Controller\Version1\PasswordController',
            'Auth\Controller\Version1\User' => 'Auth\Controller\Version1\UsersController',
        ],
    ],

    'service_manager' => [
        'abstract_factories' => [
            'Auth\Model\Service\ServiceAbstractFactory',
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
        'factories' => [
            'Request' => 'Auth\Model\Mvc\Service\RequestFactory',
            'translator' => 'Zend\Mvc\Service\TranslatorServiceFactory',
        ],
        'aliases' => [
            'RegistrationService' => 'Auth\Model\Service\RegistrationService',
            'AuthenticationService' => 'Auth\Model\Service\AuthenticationService',
            'PasswordResetService' => 'Auth\Model\Service\PasswordResetService',
            'EmailUpdateService' => 'Auth\Model\Service\EmailUpdateService',
            'StatsService' => 'Auth\Model\Service\StatsService',
            'UserManagementService' => 'Auth\Model\Service\UserManagementService',
            'PasswordChangeService' => 'Auth\Model\Service\PasswordChangeService',
            'AccountCleanupService' => 'Auth\Model\Service\AccountCleanupService',
        ],
    ],

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
            'auth/index/index'        => __DIR__ . '/../view/auth/index/index.phtml',
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
                        'route'    => 'account-cleanup',
                        'defaults' => [
                            'controller' => 'Auth\Controller\Console\AccountCleanup',
                            'action'     => 'cleanup'
                        ],
                    ],
                ],

            ],
        ],
    ],

];
