<?php

declare(strict_types=1);

use Application\Handler;
use Application\Handler\AboutYouHandler;
use Application\Handler\ChangePasswordHandler;
use Application\Handler\Lpa\LifeSustainingHandler;
use Application\Handler\Lpa\MoreInfoRequiredHandler;
use Application\Handler\LpaTypeHandler;
use Application\Handler\SessionKeepAliveHandler;
use Application\Handler\SessionSetExpiryHandler;
use Application\Handler\TypeHandler;
use Application\Handler\DeleteAccountConfirmHandler;
use Application\Handler\DeleteAccountHandler;
use Application\Handler\DashboardHandler;
use Application\Handler\Lpa\ApplicantHandler;
use Application\Handler\Lpa\ConfirmDeleteLpaHandler;
use Application\Handler\Lpa\CreateLpaHandler;
use Application\Handler\Lpa\DeleteLpaHandler;
use Application\Handler\StatusesHandler;
use Application\Handler\TermsChangedHandler;
use Application\Listener\AuthenticationListener;
use Application\Listener\TermsAndConditionsListener;
use Application\Listener\UserDetailsListener;
use Application\Handler\ChangeEmailAddressHandler;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Middleware\RouteMatchMiddleware;
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
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\HomeRedirectHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // index-redirect

            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/home',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\HomeHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // home

            'terms' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/terms',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\TermsHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // terms

            'accessibility' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/accessibility',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\AccessibilityHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // terms

            'privacy' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/privacy-notice',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\PrivacyHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // privacy

            'contact' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/contact',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\ContactHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // contact

            'cookies' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/cookies',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\CookiesHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // contact

            'forgot-password' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/forgot-password',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\ForgotPasswordHandler::class,
                        'unauthenticated_route' => true
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
                                'controller' => PipeSpec::class,
                                'middleware' => Handler\ResetPasswordHandler::class,
                                'unauthenticated_route' => true
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
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\FeedbackHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // send-feedback

            'feedback-thanks' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/feedback-thanks',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\FeedbackThanksHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // feedback-thanks

            'guidance' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/guide[/:section]',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\GuidanceHandler::class,
                        'section'    => '',
                        'unauthenticated_route' => true
                    ],
                ],
            ], // guidance

            'enable-cookie' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/enable-cookie',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\EnableCookieHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // enable-cookie

            'login' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/login[/:state]',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\LoginHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // login

            'logout' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\LogoutHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // logout

            'session-expiry' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/session-state',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\SessionExpiryHandler::class,
                        'unauthenticated_route' => true
                    ]
                ],
            ], // session state

            'session-keep-alive' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/session-keep-alive',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => new PipeSpec(
                            AuthenticationListener::class,
                            SessionKeepAliveHandler::class,
                        ),
                    ],
                ],
            ],

            'session-set-expiry' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/session-set-expiry',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => new PipeSpec(
                            AuthenticationListener::class,
                            SessionSetExpiryHandler::class,
                        ),
                    ],
                ],
            ],

            'deleted' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/deleted',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware'     => Handler\DeletedAccountHandler::class,
                        'unauthenticated_route' => true
                    ],
                ],
            ], // deleted

            'register' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/signup',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\RegisterHandler::class,
                        'unauthenticated_route' => true
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
                                'controller' => PipeSpec::class,
                                'middleware' => Handler\ConfirmRegistrationHandler::class,
                                'unauthenticated_route' => true
                            ],
                        ],
                    ],
                    'resend-email' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/resend-email',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => Handler\ResendActivationEmailHandler::class,
                                'unauthenticated_route' => true
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
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\StatsHandler::class,
                        'unauthenticated_route' => true
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
                        'unauthenticated_route' => true
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
                                'unauthenticated_route' => true
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
                                'unauthenticated_route' => true
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
                                'unauthenticated_route' => true
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
                        'controller' => PipeSpec::class,
                        'middleware' => new PipeSpec(
                            RouteMatchMiddleware::class,
                            AuthenticationListener::class,
                            UserDetailsListener::class,
                            TermsAndConditionsListener::class,
                            Handler\PostcodeHandler::class,
                        ),
                        'allowIncompleteUser' => true,
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
                            'route' => '/about-you[/:new]',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    AboutYouHandler::class,
                                ),
                                'allowIncompleteUser' => true,
                            ],
                        ],
                    ], // about-you

                    'change-email-address' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/change-email-address',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    ChangeEmailAddressHandler::class,
                                ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => Handler\VerifyEmailAddressHandler::class,
                                        'unauthenticated_route' => true,
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    ChangePasswordHandler::class,
                                ),
                            ],
                        ],
                    ], // change-password

                    'dashboard' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/dashboard',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    DashboardHandler::class,
                                ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RouteMatchMiddleware::class,
                                            AuthenticationListener::class,
                                            UserDetailsListener::class,
                                            TermsAndConditionsListener::class,
                                            DashboardHandler::class,
                                        ),
                                        'page' => 1,
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            AuthenticationListener::class,
                                            UserDetailsListener::class,
                                            TermsAndConditionsListener::class,
                                            CreateLpaHandler::class,
                                        ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            AuthenticationListener::class,
                                            UserDetailsListener::class,
                                            TermsAndConditionsListener::class,
                                            DeleteLpaHandler::class,
                                        ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            AuthenticationListener::class,
                                            UserDetailsListener::class,
                                            TermsAndConditionsListener::class,
                                            ConfirmDeleteLpaHandler::class,
                                        ),
                                    ],
                                ],
                            ],
                            'statuses' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/statuses/:lpa-ids',
                                    'constraints' => [
                                        'lpa-ids' => '[0-9,]+',
                                    ],
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            AuthenticationListener::class,
                                            UserDetailsListener::class,
                                            TermsAndConditionsListener::class,
                                            StatusesHandler::class,
                                        ),
                                    ],
                                ],
                            ],
                            'terms-changed' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/new-terms',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            AuthenticationListener::class,
                                            UserDetailsListener::class,
                                            TermsAndConditionsListener::class,
                                            TermsChangedHandler::class,
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ], // dashboard

                    'delete' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/delete',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    DeleteAccountHandler::class,
                                ),
                            ],
                        ],
                    ], // delete
                    'delete-confirm' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/delete/confirm',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    DeleteAccountConfirmHandler::class,
                                ),
                            ],
                        ],
                    ], // delete-confirm
                ],
            ], // user

            //--------------------------------------------------
            // Untyped LPA Route (Type form, no LPA ID)

            'lpa-type-no-id' => [
                'type' => Segment::class,
                'options' => [
                    'route'    => '/lpa/type',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => new PipeSpec(
                            RouteMatchMiddleware::class,
                            AuthenticationListener::class,
                            UserDetailsListener::class,
                            TermsAndConditionsListener::class,
                            LpaTypeHandler::class,
                        ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    LpaLoaderMiddleware::class,
                                    ApplicantHandler::class,
                                ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    LpaLoaderMiddleware::class,
                                    MoreInfoRequiredHandler::class,
                                ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    LpaLoaderMiddleware::class,
                                    TypeHandler::class,
                                ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RouteMatchMiddleware::class,
                                    AuthenticationListener::class,
                                    UserDetailsListener::class,
                                    TermsAndConditionsListener::class,
                                    LpaLoaderMiddleware::class,
                                    LifeSustainingHandler::class,
                                ),
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
