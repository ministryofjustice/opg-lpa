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

            'terms' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/terms-and-conditions',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'terms',
                    ],
                ],
            ], // terms

            'forgot-password' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/forgot-password',
                    'defaults' => [
                        'controller' => 'General\ForgotPasswordController',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'callback' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/reset/:token',
                            'constraints' => [
                                'token' => '[a-f0-9]+',
                            ],
                            'defaults' => [
                                'action'     => 'reset-password',
                            ],
                        ],
                    ],
                ],
            ], // forgot-password

            'send-feedback' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/send-feedback',
                    'defaults' => [
                        'controller' => 'General\FeedbackController',
                        'action'     => 'index',
                    ],
                ],
            ], // send-feedback
            
            'guidance' => [
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => [
                    'route'    => '/guide[/:section]',
                    'defaults' => [
                        'controller' => 'General\GuidanceController',
                        'action'     => 'index',
                        'section'    => '',
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
                'type' => 'Segment',
                'options' => [
                    'route'    => '/signup',
                    'defaults' => [
                        'controller' => 'General\RegisterController',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'callback' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/confirm/:token',
                            'constraints' => [
                                'token' => '[a-f0-9]+',
                            ],
                            'defaults' => [
                                'action'     => 'confirm',
                            ],
                        ],
                    ],
                ],
            ], // register

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

            //--------------------------------------------------
            // Signed in User routes

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
                    'route'    => '/address-lookup',
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
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/about-you',
                            'defaults' => [
                                'controller' => 'Authenticated\AboutYouController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'new' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/new',
                                    'defaults' => [
                                        'action'     => 'new',
                                    ],
                                ],
                            ],
                        ],
                    ], // about-you

                    'change-email-address' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/change-email-address',
                            'defaults' => [
                                'controller' => 'Authenticated\ChangeEmailAddressController',
                                'action'     => 'index',
                            ],
                        ],
                    ], // change-email-address

                    'change-password' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/change-password',
                            'defaults' => [
                                'controller' => 'Authenticated\ChangePasswordController',
                                'action'     => 'index',
                            ],
                        ],
                    ], // change-password

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
                            'create-lpa' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/create[/:lpa-id]',
                                    'constraints' => [
                                        'lpa-id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'create',
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
                            'terms-changed' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/new-terms',
                                    'defaults' => [
                                        'action'     => 'terms',
                                    ],
                                ],
                            ],
                        ],
                    ], // dashboard

                    'delete' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/delete',
                            'defaults' => [
                                'controller' => 'Authenticated\DeleteController',
                                'action'     => 'index',
                            ],
                        ],
                    ], // delete
                ],
            ], // user

            //--------------------------------------------------
            // LPA Routes

            'lpa' => [
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => [
                    'route'    => '/lpa/:lpa-id',
                    'constraints' => [
                        'lpa-id' => '[0-9]+',
                    ],
                    'defaults' => [
                            'controller' => 'Authenticated\Lpa\IndexController',
                            'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
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
                            'route'    => '/download/:pdf-type',
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
                                    'route'    => '/edit/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
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
                                    'route'    => '/edit/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
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
                    'register' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/register',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\RegisterLpaController',
                                'action'     => 'index',
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
                                    'route'    => '/edit/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
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
                    'view-docs' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/view-docs',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CompleteController',
                                'action'     => 'view-docs',
                            ],
                        ],
                    ],
                    'who-are-you' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/who-are-you',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhoAreYouController',
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

                ], // child_routes

            ], // lpa

        ], // routes

    ], // router
];


