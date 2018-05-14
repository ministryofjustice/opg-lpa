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
                            'controller' => 'Auth\Controller\Console\AccountCleanup',
                            'action'     => 'cleanup'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'router' => [
        'routes' => [
            'auth-v1' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/v1',
                    'defaults' => [
                        '__NAMESPACE__' => 'Auth\Controller\Version1',
                    ],
                ],
                'child_routes' => [

                    'stats' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/stats',
                            'defaults' => [
                                'controller' => 'StatsController',
                                'action' => 'index',
                            ],
                        ],
                    ], // stats

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

    'controllers' => [
        'factories' => [
            'Auth\Controller\Console\AccountCleanup' => 'Auth\Controller\Console\AccountCleanupControllerFactory',
            'Auth\Controller\Version1\Registration' => 'Auth\Controller\Version1\RegistrationControllerFactory',
            'Auth\Controller\Version1\Stats' => 'Auth\Controller\Version1\StatsControllerFactory',
            'Auth\Controller\Ping' => 'Auth\Controller\PingControllerFactory',
        ],
        'abstract_factories' => [
            'Auth\Controller\ControllerAbstractFactory',
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
        ],
    ],

];
