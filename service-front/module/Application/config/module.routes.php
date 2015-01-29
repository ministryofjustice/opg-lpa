<?php

return [

    'router' => [

        'routes' => [

            // ========================== General ==========================

            'index-redirect' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'redirect',
                    ],
                ],
            ], // index-redirect

            'home' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/home',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'index',
                    ],
                ],
            ], // home

            'forgot-password' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/forgot-password',
                    'defaults' => [
                        'controller' => 'General\ForgotPasswordController',
                        'action'     => 'index',
                    ],
                ],
            ], // forgot-password

            'guidance' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/guidance',
                    'defaults' => [
                        'controller' => 'General\GuidanceController',
                        'action'     => 'index',
                    ],
                ],
            ], // guidance

            'enable-cookie' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/enable-cookie',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'enable-cookie',
                    ],
                ],
            ], // enable-cookie

            'login' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/login',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action'     => 'index',
                    ],
                ],
            ], // login

            'logout' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action'     => 'logout',
                    ],
                ],
            ], // logout

            'register' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/register',
                    'defaults' => [
                        'controller' => 'General\RegisterController',
                        'action'     => 'index',
                    ],
                ],
            ], // register

            'reset-password' => [
                'type'    => 'Zend\Mvc\Router\Http\Segment',
                'options' => [
                    'route'    => '/reset-password/:password_reset_id',
                    'constraints' => [
                        'password_reset_id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => 'General\ForgotPasswordController',
                        'action'     => 'reset-password',
                    ],
                ],
            ], // reset-password

            'stats' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/stats',
                    'defaults' => [
                        'controller' => 'General\StatsController',
                        'action'     => 'index',
                    ],
                ],
            ], // stats

            'status' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/status',
                    'defaults' => [
                        'controller' => 'General\StatusController',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/pingdom',
                            'defaults' => [
                                'action'     => 'pingdom',
                            ],
                        ],
                    ],
                ],
            ], // status


            // ========================== Authenticated ==========================
            'admin-stats' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/admin/stats',
                    'defaults' => [
                        'controller' => 'Authenticated\AdminController',
                        'action'     => 'stats',
                    ],
                ],
            ],
            'postcode' => [
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/postcode',
                    'defaults' => [
                        'controller' => 'Authenticated\PostcodeController',
                        'action'     => 'index',
                    ],
                ],
            ],
            'user' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/user',
                    'defaults' => [
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'about-you' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/about-you',
                            'defaults' => [
                                'controller' => 'Authenticated\AboutYouController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'change-email-address' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/change-email-address',
                            'defaults' => [
                                'controller' => 'Authenticated\ChangeEmailAddressController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'change-password' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/change-password',
                            'defaults' => [
                                'controller' => 'Authenticated\ChangePasswordController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'dashboard' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/dashboard',
                            'defaults' => [
                                'controller' => 'Authenticated\DashboardController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'clone' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/clone/:lpa-id',
                                    'constraints' => [
                                        'lpa-id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'clone',
                                    ],
                                ],
                            ],
                            'delete-lpa' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete-lpa/:lpa-id',
                                    'constraints' => [
                                        'lpa-id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'delete-lpa',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'delete' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/delete',
                            'defaults' => [
                                'controller' => 'Authenticated\DeleteController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                ],
            ],

            'lpa' => [
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => [
                    'route'    => '/lpa/:lpa-id',
                    'constraints' => [
                        'lpa-id' => '[0-9]+',
                    ],
                    'defaults' => [
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'applicant' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/applicant',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\ApplicantController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'certificate-provider' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/certificate-provider',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CertificateProviderController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'complete' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/complete',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CompleteController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'correspondent' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/correspondent',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CorrespondentController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'edit' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'created' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/created',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CreatedController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'donor' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/donor',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\DonorController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'download' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/download/:pdf_type',
                            'constraints' => [
                                'pdf_type' => 'lp1|lp3|lpa120',
                            ],
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\DownloadController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'fee' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/fee',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\FeeController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'form-type' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/type',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\TypeController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'how-primary-attorneys-make-decision' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/how-primary-attorneys-make-decision',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'how-replacement-attorneys-make-decision' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/how-replacement-attorneys-make-decision',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'instructions' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/instructions',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\InstructionsController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'life-sustaining' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/life-sustaining',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\LifeSustainingController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'payment' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/payment',
                            'defaults' => array(
                                'controller' => 'Authenticated\Lpa\PaymentController',
                                'action'     => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'return' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route'    => '/return',
                                    'defaults' => array(
                                        'controller' => 'Authenticated\Lpa\PaymentCallbackController',
                                    ),
                                ),
                                'may_terminate' => false,
                                'child_routes' => array(
                                    'success' => array(
                                        'type'    => 'Literal',
                                        'options' => array(
                                            'route'    => '/success',
                                            'defaults' => array(
                                                'action' => 'success',
                                            ),
                                        ),
                                    ),
                                    'pending' => array(
                                        'type'    => 'Literal',
                                        'options' => array(
                                            'route'    => '/pending',
                                            'defaults' => array(
                                                'action' => 'pending',
                                            ),
                                        ),
                                    ),
                                    'cancel' => array(
                                        'type'    => 'Literal',
                                        'options' => array(
                                            'route'    => '/cancel',
                                            'defaults' => array(
                                                'action' => 'cancel',
                                            ),
                                        ),
                                    ),
                                    'failure' => array(
                                        'type'    => 'Literal',
                                        'options' => array(
                                            'route'    => '/failure',
                                            'defaults' => array(
                                                'action' => 'failure',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'people-to-notify' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/people-to-notify',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\PeopleToNotifyController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/edit/:person_index',
                                    'constraints' => [
                                        'person_index' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:person_index',
                                    'constraints' => [
                                        'person_index' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'primary-attorney' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/primary-attorney',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\PrimaryAttorneyController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/edit/:person_index',
                                    'constraints' => [
                                        'person_index' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:person_index',
                                    'constraints' => [
                                        'person_index' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                            'add-trust' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/add-trust',
                                    'defaults' => [
                                        'action' => 'add-trust',
                                    ],
                                ],
                            ],
                            'edit-trust' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/edit-trust',
                                    'defaults' => [
                                        'action' => 'edit-trust',
                                    ],
                                ],
                            ],
                            'delete-trust' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/delete-trust',
                                    'defaults' => [
                                        'action' => 'delete-trust',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'replacement-attorney' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/replacement-attorney',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\ReplacementAttorneyController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/edit/:person_index',
                                    'constraints' => [
                                        'person_index' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:person_index',
                                    'constraints' => [
                                        'person_index' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'what-is-my-role' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/what-is-my-role',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhatIsMyRoleController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'when-lpa-starts' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/when-lpa-starts',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhenLpaStartsController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'when-replacement-attorney-step-in' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/when-replacement-attorney-step-in',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhenReplacementAttorneyStepInController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                ],
            ],

        ], // routes

    ], // router
];


