<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),

            'v1' => [
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

        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Application\Model\Service\ServiceAbstractFactory',
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'factories' => array(
            'Request' => 'Application\Model\Mvc\Service\RequestFactory',
            'translator' => 'Zend\Mvc\Service\TranslatorServiceFactory',
        ),
        'aliases' => [
            'RegistrationService' => 'Application\Model\Service\RegistrationService',
            'AuthenticationService' => 'Application\Model\Service\AuthenticationService',
            'PasswordResetService' => 'Application\Model\Service\PasswordResetService',
            'EmailUpdateService' => 'Application\Model\Service\EmailUpdateService',
            'StatsService' => 'Application\Model\Service\StatsService',
            'UserManagementService' => 'Application\Model\Service\UserManagementService',
            'PasswordChangeService' => 'Application\Model\Service\PasswordChangeService',
            'AccountCleanupService' => 'Application\Model\Service\AccountCleanupService',
        ],
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'abstract_factories' => array(
            'Application\Controller\Version1\AuthenticatedControllerAbstractFactory',
        ),
        'factories' => array(
            'Application\Controller\Console\AccountCleanup' => 'Application\Controller\Console\AccountCleanupControllerFactory',
            'Application\Controller\Version1\Registration' => 'Application\Controller\Version1\RegistrationControllerFactory',
            'Application\Controller\Version1\Stats' => 'Application\Controller\Version1\StatsControllerFactory',
            'Application\Controller\Ping' => 'Application\Controller\PingControllerFactory',
        ),
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
        ),
        'aliases' => [
            'Application\Controller\Version1\Authenticate' => 'Application\Controller\Version1\AuthenticateController',
            'Application\Controller\Version1\Email' => 'Application\Controller\Version1\EmailController',
            'Application\Controller\Version1\Password' => 'Application\Controller\Version1\PasswordController',
            'Application\Controller\Version1\User' => 'Application\Controller\Version1\UsersController',
        ],
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ),

    // Placeholder for console routes
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

            ],
        ],
    ],

);
