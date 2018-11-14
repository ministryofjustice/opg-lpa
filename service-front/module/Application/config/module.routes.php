<?php

return [

    'router' => [

        'routes' => [

            // ========================== General ==========================

            'index-redirect' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'redirect',
                    ],
                ],
            ], // index-redirect

            'home' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/home',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'index',
                    ],
                ],
            ], // home

            'terms' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/terms',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'terms',
                    ],
                ],
            ], // terms

            'privacy' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/privacy-notice',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'privacy',
                    ],
                ],
            ], // privacy

            'contact' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/contact',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'contact',
                    ],
                ],
            ], // contact

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
                                'token' => '[a-zA-Z0-9]+',
                            ],
                            'defaults' => [
                                'action'     => 'reset-password',
                            ],
                        ],
                    ],
                ],
            ], // forgot-password

            'send-feedback' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/send-feedback',
                    'defaults' => [
                        'controller' => 'General\FeedbackController',
                        'action'     => 'index',
                    ],
                ],
            ], // send-feedback

            'sendgrid-bounce' => [
                'type' => 'Zend\Router\Http\Segment',
                'options' => [
                    'route'    => '/email/bounce/:token',
                    'constraints' => [
                        'token' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => 'General\SendgridController',
                        'action'     => 'bounce',
                    ],
                ],
            ], // sendgrid-bounce

            'guidance' => [
                'type' => 'Zend\Router\Http\Segment',
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
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/enable-cookie',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'enable-cookie',
                    ],
                ],
            ], // enable-cookie

            'login' => [
                'type' => 'Zend\Router\Http\Segment',
                'options' => [
                    'route'    => '/login[/:state]',
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

            'session-keep-alive' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/session-keep-alive',
                    'defaults' => [
                        'controller' => 'Authenticated\SessionKeepAliveController',
                        'action'     => 'index',
                    ],
                ]
            ],

            'deleted' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/deleted',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action'     => 'deleted',
                    ],
                ],
            ], // deleted

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
                    'confirm' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/confirm/:token',
                            'constraints' => [
                                'token' => '[a-zA-Z0-9]+',
                            ],
                            'defaults' => [
                                'action'     => 'confirm',
                            ],
                        ],
                    ],
                    'email-sent' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/email-sent',
                            'defaults' => [
                                'action' => 'email-sent',
                            ],
                        ],
                    ],
                    'resend-email' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/resend-email',
                            'defaults' => [
                                'action' => 'resend-email',
                            ],
                        ],
                    ],
                ],
            ], // register

            'stats' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/stats',
                    'defaults' => [
                        'controller' => 'General\StatsController',
                        'action'     => 'index',
                    ],
                ],
            ], // stats

            'ping' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/ping',
                    'defaults' => [
                        'controller' => 'General\PingController',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'json' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/json',
                            'defaults' => [
                                'action'     => 'json',
                            ],
                        ],
                    ],
                    'elb' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/elb',
                            'defaults' => [
                                'action'     => 'elb',
                            ],
                        ],
                    ],
                    'pingdom' => [
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

            'admin-system-message' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/admin/system-message',
                    'defaults' => [
                        'controller' => 'Authenticated\AdminController',
                        'action'     => 'system-message',
                    ],
                ],
            ],

            'admin-user-search' => [
                'type' => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/admin/user',
                    'defaults' => [
                        'controller' => 'Authenticated\AdminController',
                        'action'     => 'user-search',
                    ],
                ],
            ],

            'postcode' => [
                'type'    => 'Zend\Router\Http\Literal',
                'options' => [
                    'route'    => '/address-lookup',
                    'defaults' => [
                        'controller' => 'Authenticated\PostcodeController',
                        'action'     => 'index',
                    ],
                ],
            ],

            'user' => [
                'type' => 'Zend\Router\Http\Literal',
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
                            'route'    => '/about-you[/:new]',
                            'defaults' => [
                                'controller' => 'Authenticated\AboutYouController',
                                'action'     => 'index',
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
                        'may_terminate' => true,
                        'child_routes' => [
                            'verify' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/verify/:token',
                                    'constraints' => [
                                        'token' => '[a-zA-Z0-9]+',
                                    ],
                                    'defaults' => [
                                        'controller' => 'General\VerifyEmailAddressController',
                                        'action'     => 'verify',
                                    ],
                                ],
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
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/dashboard',
                            'defaults' => [
                                'controller' => 'Authenticated\DashboardController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'pagination' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/page/:page',
                                    'constraints' => [
                                        'page' => '[1-9]+[0-9]*',
                                    ],
                                    'defaults' => [
                                        'page' => 1
                                    ],
                                ],
                            ],
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
                            'confirm-delete-lpa' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/confirm-delete-lpa/:lpa-id',
                                    'constraints' => [
                                        'lpa-id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'confirm-delete-lpa',
                                    ],
                                ],
                            ],
                        ],
                    ], // dashboard

                    'delete' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/delete[/:action]',
                            'defaults' => [
                                'controller' => 'Authenticated\DeleteController',
                                'action'     => 'index',
                            ],
                        ],
                    ], // delete
                ],
            ], // user

            //--------------------------------------------------
            // Untyped LPA Route (Type form, no LPA ID)

            'lpa-type-no-id' => [
                'type' => 'Zend\Router\Http\Segment',
                'options' => [
                    'route'    => '/lpa/type',
                    'defaults' => [
                        'controller' => 'Authenticated\TypeController',
                        'action'     => 'index',
                    ],
                ],
            ],

            //--------------------------------------------------
            // LPA Routes

            'lpa' => [
                'type' => 'Zend\Router\Http\Segment',
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
                            'confirm-delete' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/confirm-delete',
                                    'defaults' => [
                                        'action' => 'confirm-delete',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'action' => 'delete',
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
                    'more-info-required' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/more-info-required',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\MoreInfoRequiredController',
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
                    'date-check' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/date-check',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\DateCheckController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'complete' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route' => '/complete',
                                ],
                            ],
                            'valid' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'  => '/valid',
                                    'defaults' => [
                                        'action' => 'valid',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'summary' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/summary',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\SummaryController',
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
                                'pdf-type' => 'lp1|lp3|lpa120',
                            ],
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\DownloadController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'draft' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/draft',
                                    'defaults' => [
                                        'action' => 'index',
                                    ],
                                ],
                            ],
                            'file' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/:pdf-filename',
                                    'constraints' => [
                                        'pdf-filename' => '[a-zA-Z0-9-]+\.pdf',
                                    ],
                                    'defaults' => [
                                        'action' => 'download',
                                    ],
                                ],
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
                    'checkout' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/checkout',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CheckoutController',
                                'action'     => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'cheque' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route'    => '/cheque',
                                    'defaults' => [
                                        'action'     => 'cheque',
                                    ],
                                ],
                            ],
                            'pay' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route'    => '/pay',
                                    'defaults' => [
                                        'action'     => 'pay',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'response' => array(
                                        'type'    => 'Literal',
                                        'options' => array(
                                            'route'    => '/response',
                                            'defaults' => array(
                                                'action' => 'payResponse',
                                            ),
                                        ),
                                    ),
                                ],
                            ],
                            'confirm' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route'    => '/confirm',
                                    'defaults' => [
                                        'action'     => 'confirm',
                                    ],
                                ],
                            ],
                        ],
                    ],
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
                            'confirm-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/confirm-delete/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'confirm-delete',
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
                            'confirm-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/confirm-delete/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'confirm-delete',
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
                        ],
                    ],
                    'fee-reduction' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/fee-reduction',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\FeeReductionController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'repeat-application' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/repeat-application',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\RepeatApplicationController',
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
                            'confirm-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/confirm-delete/:idx',
                                    'constraints' => [
                                        'idx' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'confirm-delete',
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
                    'reuse-details' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/reuse-details',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\ReuseDetailsController',
                                'action' => 'index',
                            ],
                        ],
                    ],

                ], // child_routes

            ], // lpa

        ], // routes

    ], // router
];
