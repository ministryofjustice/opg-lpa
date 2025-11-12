<?php

declare(strict_types=1);

use Application\Handler;
use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [

    'router' => [

        'routes' => [

            // ========================== General ==========================

            'index-redirect' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'redirect',
                    ],
                ],
            ], // index-redirect

            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/home',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'index',
                    ],
                ],
            ], // home

            'terms' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/terms',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'terms',
                    ],
                ],
            ], // terms

            'accessibility' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/accessibility',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'accessibility',
                    ],
                ],
            ], // terms

            'privacy' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/privacy-notice',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'privacy',
                    ],
                ],
            ], // privacy

            'contact' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/contact',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'contact',
                    ],
                ],
            ], // contact

            'cookies' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/cookies',
                    'defaults' => [
                        'controller' => 'General\CookiesController',
                        'action'     => 'index',
                    ],
                ],
            ], // contact

            'forgot-password' => [
                'type' => Segment::class,
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
                        'type'    => Segment::class,
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
                'type' => Literal::class,
                'options' => [
                    'route'    => '/send-feedback',
                    'defaults' => [
                        'controller' => 'General\FeedbackController',
                        'action'     => 'index',
                    ],
                ],
            ], // send-feedback

            'feedback-thanks' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/feedback-thanks',
                    'defaults' => [
                        'controller' => 'General\FeedbackController',
                        'action'     => 'thanks',
                    ],
                ],
            ], // feedback-thanks

            'guidance' => [
                'type' => Segment::class,
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
                'type' => Literal::class,
                'options' => [
                    'route'    => '/enable-cookie',
                    'defaults' => [
                        'controller' => 'General\HomeController',
                        'action'     => 'enable-cookie',
                    ],
                ],
            ], // enable-cookie

            'login' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/login[/:state]',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action'     => 'index',
                    ],
                ],
            ], // login

            'logout' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action'     => 'logout',
                    ],
                ],
            ], // logout

            'session-expiry' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/session-state',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action' => 'session-expiry',
                    ]
                ],
            ], // session state

            'session-keep-alive' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/session-keep-alive',
                    'defaults' => [
                        'controller' => 'Authenticated\SessionKeepAliveController',
                        'action'     => 'index',
                    ],
                ]
            ],

            'session-set-expiry' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/session-set-expiry',
                    'defaults' => [
                        'controller' => 'Authenticated\SessionKeepAliveController',
                        'action'     => 'setExpiry',
                    ],
                ]
            ],


            'deleted' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/deleted',
                    'defaults' => [
                        'controller' => 'General\AuthController',
                        'action'     => 'deleted',
                    ],
                ],
            ], // deleted

            'register' => [
                'type' => Segment::class,
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
                        'type'    => Segment::class,
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
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/email-sent',
                            'defaults' => [
                                'action' => 'email-sent',
                            ],
                        ],
                    ],
                    'resend-email' => [
                        'type'    => Literal::class,
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
                'type' => Literal::class,
                'options' => [
                    'route'    => '/stats',
                    'defaults' => [
                        'controller' => 'General\StatsController',
                        'action'     => 'index',
                    ],
                ],
            ], // stats

            'ping' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/ping',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\PingHandler::class,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'json' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/json',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware'     => Handler\PingHandlerJson::class,
                            ],
                        ],
                    ],
                    'elb' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/elb',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware'     => Handler\PingHandlerElb::class,
                            ],
                        ],
                    ],
                    'pingdom' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/pingdom',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware'     => Handler\PingHandlerPingdom::class,
                            ],
                        ],
                    ],
                ],
            ], // status

            //--------------------------------------------------
            // Signed in User routes

            'postcode' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/address-lookup',
                    'defaults' => [
                        'controller' => 'Authenticated\PostcodeController',
                        'action'     => 'index',
                    ],
                ],
            ],

            'user' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/user',
                    'defaults' => [
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [

                    'about-you' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/about-you[/:new]',
                            'defaults' => [
                                'controller' => 'Authenticated\AboutYouController',
                                'action'     => 'index',
                            ],
                        ],
                    ], // about-you

                    'change-email-address' => [
                        'type'    => Literal::class,
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
                                'type'    => Segment::class,
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
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/change-password',
                            'defaults' => [
                                'controller' => 'Authenticated\ChangePasswordController',
                                'action'     => 'index',
                            ],
                        ],
                    ], // change-password

                    'dashboard' => [
                        'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                            'statuses' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/statuses/:lpa-ids',
                                    'constraints' => [
                                        'lpa-id' => '[0-9,]+',
                                    ],
                                    'defaults' => [
                                        'action'     => 'statuses',
                                    ],
                                ],
                            ],
                            'terms-changed' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/new-terms',
                                    'defaults' => [
                                        'action'     => 'terms',
                                    ],
                                ],
                            ],
                            'confirm-delete-lpa' => [
                                'type'    => Segment::class,
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
                        'type'    => Segment::class,
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
                'type' => Segment::class,
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
                'type' => Segment::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/applicant',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\ApplicantController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'certificate-provider' => [
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'confirm-delete' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/confirm-delete',
                                    'defaults' => [
                                        'action' => 'confirm-delete',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => Literal::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/complete',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CompleteController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'more-info-required' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/more-info-required',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\MoreInfoRequiredController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'correspondent' => [
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
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
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route' => '/complete',
                                ],
                            ],
                            'valid' => [
                                'type'    => Literal::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/summary',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\SummaryController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'donor' => [
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Literal::class,
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
                        'type' => Segment::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/draft',
                                    'defaults' => [
                                        'action' => 'index',
                                    ],
                                ],
                            ],
                            'file' => [
                                'type'    => Segment::class,
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
                            'check' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/check',
                                    'defaults' => [
                                        'action' => 'check',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'form-type' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/type',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\TypeController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'how-primary-attorneys-make-decision' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/how-primary-attorneys-make-decision',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'how-replacement-attorneys-make-decision' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/how-replacement-attorneys-make-decision',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'instructions' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/instructions',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\InstructionsController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'life-sustaining' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/life-sustaining',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\LifeSustainingController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'checkout' => [
                        'type' => Literal::class,
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
                                'type' => Literal::class,
                                'options' => [
                                    'route'    => '/cheque',
                                    'defaults' => [
                                        'action'     => 'cheque',
                                    ],
                                ],
                            ],
                            'pay' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route'    => '/pay',
                                    'defaults' => [
                                        'action'     => 'pay',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'response' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/response',
                                            'defaults' => [
                                                'action' => 'payResponse',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'confirm' => [
                                'type' => Literal::class,
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
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Literal::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/fee-reduction',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\FeeReductionController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'repeat-application' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/repeat-application',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\RepeatApplicationController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'replacement-attorney' => [
                        'type' => Literal::class,
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
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Segment::class,
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
                                'type'    => Literal::class,
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
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/view-docs',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\CompleteController',
                                'action'     => 'view-docs',
                            ],
                        ],
                    ],
                    'who-are-you' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/who-are-you',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhoAreYouController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'when-lpa-starts' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/when-lpa-starts',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhenLpaStartsController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'when-replacement-attorney-step-in' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/when-replacement-attorney-step-in',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\WhenReplacementAttorneyStepInController',
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'reuse-details' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/reuse-details',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\ReuseDetailsController',
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'status' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/status',
                            'defaults' => [
                                'controller' => 'Authenticated\Lpa\StatusController',
                                'action'     => 'index',
                            ],
                        ],
                    ],

                ], // child_routes

            ], // lpa

        ], // routes

    ], // router
];
