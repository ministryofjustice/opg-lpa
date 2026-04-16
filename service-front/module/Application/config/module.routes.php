<?php

declare(strict_types=1);

use Application\Handler;
use Application\Handler\AboutYouHandler;
use Application\Handler\ChangePasswordHandler;
use Application\Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler;
use Application\Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler;
use Application\Handler\Lpa\InstructionsHandler;
use Application\Handler\Lpa\LifeSustainingHandler;
use Application\Handler\Lpa\MoreInfoRequiredHandler;
use Application\Handler\Lpa\SummaryHandler;
use Application\Handler\Lpa\WhoAreYouHandler;
use Application\Handler\Lpa\WhenReplacementAttorneyStepInHandler;
use Application\Handler\LpaTypeHandler;
use Application\Handler\SessionKeepAliveHandler;
use Application\Handler\SessionSetExpiryHandler;
use Application\Handler\TypeHandler;
use Application\Handler\DeleteAccountConfirmHandler;
use Application\Handler\DeleteAccountHandler;
use Application\Handler\DashboardHandler;
use Application\Handler\Lpa\ApplicantHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderAddHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderHandler;
use Application\Handler\Lpa\CheckoutChequeHandler;
use Application\Handler\Lpa\CheckoutConfirmHandler;
use Application\Handler\Lpa\CheckoutIndexHandler;
use Application\Handler\Lpa\CheckoutPayHandler;
use Application\Handler\Lpa\CheckoutPayResponseHandler;
use Application\Handler\Lpa\ConfirmDeleteLpaHandler;
use Application\Handler\Lpa\CreateLpaHandler;
use Application\Handler\Lpa\DeleteLpaHandler;
use Application\Handler\Lpa\CompleteIndexHandler;
use Application\Handler\Lpa\CompleteViewDocsHandler;
use Application\Handler\Lpa\DonorAddHandler;
use Application\Handler\Lpa\DonorEditHandler;
use Application\Handler\Lpa\DonorIndexHandler;
use Application\Handler\Lpa\ReplacementAttorneyAddHandler;
use Application\Handler\Lpa\ReplacementAttorneyAddTrustHandler;
use Application\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use Application\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use Application\Handler\Lpa\ReplacementAttorneyEditHandler;
use Application\Handler\Lpa\ReplacementAttorneyIndexHandler;
use Application\Handler\Lpa\FeeReductionHandler;
use Application\Handler\Lpa\ReuseDetailsHandler;
use Application\Handler\Lpa\RepeatApplicationHandler;
use Application\Handler\Lpa\WhenLpaStartsHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use Application\Handler\Lpa\PrimaryAttorneyHandler;
use Application\Handler\Lpa\CorrespondentHandler;
use Application\Handler\Lpa\CorrespondentEditHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use Application\Handler\Lpa\StatusHandler;
use Application\Handler\Lpa\DateCheckHandler;
use Application\Handler\Lpa\DateCheckValidHandler;
use Application\Handler\StatusesHandler;
use Application\Handler\TermsChangedHandler;
use Application\Listener\TermsAndConditionsListener;
use Application\Listener\UserDetailsListener;
use Application\Handler\ChangeEmailAddressHandler;
use Application\Helper\RouteMiddlewareHelper;
use Application\Middleware\LpaLoaderMiddleware;
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
                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                            SessionKeepAliveHandler::class,
                            [
                                UserDetailsListener::class,
                                TermsAndConditionsListener::class,
                                LpaLoaderMiddleware::class,
                            ]
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
                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                            SessionSetExpiryHandler::class,
                            [
                                UserDetailsListener::class,
                                TermsAndConditionsListener::class,
                                LpaLoaderMiddleware::class,
                            ]
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
                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                            Handler\PostcodeHandler::class,
                            [LpaLoaderMiddleware::class]
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(AboutYouHandler::class, []),
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(ChangeEmailAddressHandler::class, []),
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    ChangePasswordHandler::class,
                                    [LpaLoaderMiddleware::class]
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    DashboardHandler::class,
                                    [LpaLoaderMiddleware::class]
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
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            DashboardHandler::class,
                                            [LpaLoaderMiddleware::class]
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
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            CreateLpaHandler::class,
                                            [LpaLoaderMiddleware::class]
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
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            DeleteLpaHandler::class,
                                            [LpaLoaderMiddleware::class]
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
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            ConfirmDeleteLpaHandler::class,
                                            [LpaLoaderMiddleware::class]
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
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            StatusesHandler::class,
                                            [LpaLoaderMiddleware::class]
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
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            TermsChangedHandler::class,
                                            [LpaLoaderMiddleware::class]
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    DeleteAccountHandler::class,
                                    [LpaLoaderMiddleware::class]
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    DeleteAccountConfirmHandler::class,
                                    [LpaLoaderMiddleware::class]
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
                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                            LpaTypeHandler::class,
                            [LpaLoaderMiddleware::class]
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(ApplicantHandler::class, []),
                            ],
                        ],
                    ],
                    'certificate-provider' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/certificate-provider',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(CertificateProviderHandler::class, []),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(CertificateProviderAddHandler::class, []),
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(CertificateProviderEditHandler::class, []),
                                    ],
                                ],
                            ],
                            'confirm-delete' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/confirm-delete',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(CertificateProviderConfirmDeleteHandler::class, []),
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(CertificateProviderDeleteHandler::class, []),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(CompleteIndexHandler::class, []),
                            ],
                        ],
                    ],
                    'more-info-required' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/more-info-required',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(MoreInfoRequiredHandler::class, []),
                            ],
                        ],
                    ],
                    'correspondent' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/correspondent',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(CorrespondentHandler::class, []),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'edit' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(CorrespondentEditHandler::class, []),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    DateCheckHandler::class,
                                    [],
                                ),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'complete' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route' => '/complete',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            DateCheckHandler::class,
                                            [],
                                        ),
                                    ],
                                ],
                            ],
                            'valid' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'  => '/valid',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            DateCheckValidHandler::class,
                                            [],
                                        ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(SummaryHandler::class, []),
                            ],
                        ],
                    ],
                    'donor' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/donor',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(DonorIndexHandler::class, []),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(DonorAddHandler::class, []),
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/edit',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(DonorEditHandler::class, []),
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
                                'middleware' => RouteMiddlewareHelper::addMiddleware(TypeHandler::class, []),
                            ],
                        ],
                    ],
                    'how-primary-attorneys-make-decision' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/how-primary-attorneys-make-decision',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(HowPrimaryAttorneysMakeDecisionHandler::class, []),
                            ],
                        ],
                    ],
                    'how-replacement-attorneys-make-decision' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/how-replacement-attorneys-make-decision',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    HowReplacementAttorneysMakeDecisionHandler::class,
                                    [],
                                ),
                            ],
                        ],
                    ],
                    'instructions' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/instructions',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    InstructionsHandler::class,
                                    []
                                ),
                            ],
                        ],
                    ],
                    'life-sustaining' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/life-sustaining',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    LifeSustainingHandler::class,
                                    []
                                ),
                            ],
                        ],
                    ],
                    'checkout' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/checkout',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    CheckoutIndexHandler::class,
                                    []
                                ),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'cheque' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route'    => '/cheque',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            CheckoutChequeHandler::class,
                                            []
                                        ),
                                    ],
                                ],
                            ],
                            'pay' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route'    => '/pay',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            CheckoutPayHandler::class,
                                            []
                                        ),
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'response' => [
                                        'type'    => Literal::class,
                                        'options' => [
                                            'route'    => '/response',
                                            'defaults' => [
                                                'controller' => PipeSpec::class,
                                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                                    CheckoutPayResponseHandler::class,
                                                    []
                                                ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            CheckoutConfirmHandler::class,
                                            []
                                        ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(PeopleToNotifyHandler::class, []),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PeopleToNotifyAddHandler::class, []),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PeopleToNotifyEditHandler::class, []),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PeopleToNotifyConfirmDeleteHandler::class, []),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PeopleToNotifyDeleteHandler::class, []),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(PrimaryAttorneyHandler::class, []),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PrimaryAttorneyAddHandler::class, []),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PrimaryAttorneyEditHandler::class, []),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PrimaryAttorneyConfirmDeleteHandler::class, []),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PrimaryAttorneyDeleteHandler::class, []),
                                    ],
                                ],
                            ],
                            'add-trust' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-trust',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(PrimaryAttorneyAddTrustHandler::class, []),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(FeeReductionHandler::class, []),
                            ],
                        ],
                    ],
                    'repeat-application' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/repeat-application',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    RepeatApplicationHandler::class,
                                    [],
                                ),
                            ],
                        ],
                    ],
                    'replacement-attorney' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/replacement-attorney',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    ReplacementAttorneyIndexHandler::class,
                                    []
                                ),
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'add' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            ReplacementAttorneyAddHandler::class,
                                            []
                                        ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            ReplacementAttorneyEditHandler::class,
                                            []
                                        ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            ReplacementAttorneyConfirmDeleteHandler::class,
                                            []
                                        ),
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
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            ReplacementAttorneyDeleteHandler::class,
                                            []
                                        ),
                                    ],
                                ],
                            ],
                            'add-trust' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-trust',
                                    'defaults' => [
                                        'controller' => PipeSpec::class,
                                        'middleware' => RouteMiddlewareHelper::addMiddleware(
                                            ReplacementAttorneyAddTrustHandler::class,
                                            []
                                        ),
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
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(CompleteViewDocsHandler::class, []),
                            ],
                        ],
                    ],
                    'who-are-you' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/who-are-you',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(WhoAreYouHandler::class, []),
                            ],
                        ],
                    ],
                    'when-lpa-starts' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/when-lpa-starts',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(WhenLpaStartsHandler::class, []),
                            ],
                        ],
                    ],
                    'when-replacement-attorney-step-in' => [
                        'type' => Literal::class,
                        'options' => [
                            'route'    => '/when-replacement-attorney-step-in',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(
                                    WhenReplacementAttorneyStepInHandler::class,
                                    []
                                ),
                            ],
                        ],
                    ],
                    'reuse-details' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/reuse-details',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(ReuseDetailsHandler::class, []),
                            ],
                        ],
                    ],
                    'status' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/status',
                            'defaults' => [
                                'controller' => PipeSpec::class,
                                'middleware' => RouteMiddlewareHelper::addMiddleware(StatusHandler::class, []),
                            ],
                        ],
                    ],

                ], // child_routes

            ], // lpa

        ], // routes

    ], // router
];
