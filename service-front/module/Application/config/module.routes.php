<?php

return array(
    // ====== General =======
    'index-redirect' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/',
            'defaults' => array(
                'controller' => 'General\HomeController',
                'action'     => 'redirect',
            ),
        ),
    ), // index-redirect

    'home' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/home',
            'defaults' => array(
                'controller' => 'General\HomeController',
                'action'     => 'index',
            ),
        ),
    ), // home

    'forgot-password' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/forgot-password',
            'defaults' => array(
                'controller' => 'General\ForgotPasswordController',
                'action'     => 'index',
            ),
        ),
    ), // forgot-password

    'guidance' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/guidance',
            'defaults' => array(
                'controller' => 'General\GuidanceController',
                'action'     => 'index',
            ),
        ),
    ), // guidance

    'enable-cookie' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/enable-cookie',
            'defaults' => array(
                'controller' => 'General\HomeController',
                'action'     => 'enable-cookie',
            ),
        ),
    ), // enable-cookie

    'login' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/login',
            'defaults' => array(
                'controller' => 'General\AuthController',
                'action'     => 'index',
            ),
        ),
    ), // login

    'logout' => array(
        'type'    => 'Literal',
        'options' => array(
            'route'    => '/logout',
            'defaults' => array(
                'controller' => 'General\AuthController',
                'action'     => 'logout',
            ),
        ),
    ), // logout

    'register' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/register',
            'defaults' => array(
                'controller' => 'General\RegisterController',
                'action'     => 'index',
            ),
        ),
    ), // register

    'reset-password' => array(
        'type'    => 'Zend\Mvc\Router\Http\Segment',
        'options' => array(
            'route'    => '/reset-password/:password_reset_id',
            'constraints' => array(
                'password_reset_id' => '[a-zA-Z0-9]+',
            ),
            'defaults' => array(
                'controller' => 'General\ForgotPasswordController',
                'action'     => 'reset-password',
            ),
        ),
    ), // reset-password

    'stats' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/stats',
            'defaults' => array(
                'controller' => 'General\StatsController',
                'action'     => 'index',
            ),
        ),
    ), // stats

    'status' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/status',
            'defaults' => array(
                'controller' => 'General\StatusController',
                'action'     => 'index',
            ),
        ),
        'may_terminate' => true,
        'child_routes' => array(
            'default' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/pingdom',
                    'defaults' => array(
                        'action'     => 'pingdom',
                    ),
                ),
            ),
        ),
    ), // status


    // ====== Authenticated =======
    'admin-stats' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/admin/stats',
            'defaults' => array(
                'controller' => 'Authenticated\AdminController',
                'action'     => 'stats',
            ),
        ),
    ),
    'postcode' => array(
        'type'    => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/postcode',
            'defaults' => array(
                'controller' => 'Authenticated\PostcodeController',
                'action'     => 'index',
            ),
        ),
    ),
    'user' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/user',
            'defaults' => array(
            ),
        ),
        'may_terminate' => false,
        'child_routes' => array(
            'about-you' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/about-you',
                    'defaults' => array(
                        'controller' => 'Authenticated\AboutYouController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'change-email-address' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/change-email-address',
                    'defaults' => array(
                        'controller' => 'Authenticated\ChangeEmailAddressController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'change-password' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/change-password',
                    'defaults' => array(
                        'controller' => 'Authenticated\ChangePasswordController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'dashboard' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/dashboard',
                    'defaults' => array(
                        'controller' => 'Authenticated\DashboardController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'clone' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/clone/:lpa-id',
                            'constraints' => array(
                                'lpa-id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action'     => 'clone',
                            ),
                        ),
                    ),
                    'delete-lpa' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/delete-lpa/:lpa-id',
                            'constraints' => array(
                                'lpa-id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action'     => 'delete-lpa',
                            ),
                        ),
                    ),
                ),
            ),
            'delete' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/delete',
                    'defaults' => array(
                        'controller' => 'Authenticated\DeleteController',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),

    'lpa' => array(
        'type' => 'Zend\Mvc\Router\Http\Segment',
        'options' => array(
            'route'    => '/lpa/:lpa-id',
            'constraints' => array(
                'lpa-id' => '[0-9]+',
            ),
            'defaults' => array(
            ),
        ),
        'may_terminate' => false,
        'child_routes' => array(
            'applicant' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/applicant',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\ApplicantController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'certificate-provider' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/certificate-provider',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\CertificateProviderController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'add' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'add',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/edit',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                ),
            ),
            'complete' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/complete',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\CompleteController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'correspondent' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/correspondent',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\CorrespondentController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'edit' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/edit',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                ),
            ),
            'created' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/created',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\CreatedController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'donor' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/donor',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\DonorController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'add' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'add',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/edit',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                ),
            ),
            'download' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/download/:pdf_type',
                    'constraints' => array(
                        'pdf_type' => 'lp1|lp3|lpa120',
                    ),
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\DownloadController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'fee' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/fee',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\FeeController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'form-type' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/type',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\TypeController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'how-primary-attorneys-make-decision' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/how-primary-attorneys-make-decision',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'how-replacement-attorneys-make-decision' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/how-replacement-attorneys-make-decision',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'instructions' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/instructions',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\InstructionsController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'life-sustaining' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/life-sustaining',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\LifeSustainingController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'payment' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/payment',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\PaymentController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'payment-callback' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/payment-return',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\PaymentCallbackController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'people-to-notify' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/people-to-notify',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\PeopleToNotifyController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'add' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'add',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/edit/:person_index',
                            'constraints' => array(
                                'person_index' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/delete/:person_index',
                            'constraints' => array(
                                'person_index' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                        ),
                    ),
                ),
            ),
            'primary-attorney' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/primary-attorney',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\PrimaryAttorneyController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'add' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'add',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/edit/:person_index',
                            'constraints' => array(
                                'person_index' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/delete/:person_index',
                            'constraints' => array(
                                'person_index' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                        ),
                    ),
                    'add-trust' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/add-trust',
                            'defaults' => array(
                                'action' => 'add-trust',
                            ),
                        ),
                    ),
                    'edit-trust' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/edit-trust',
                            'defaults' => array(
                                'action' => 'edit-trust',
                            ),
                        ),
                    ),
                    'delete-trust' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/delete-trust',
                            'defaults' => array(
                                'action' => 'delete-trust',
                            ),
                        ),
                    ),
                ),
            ),
            'replacement-attorney' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/replacement-attorney',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\ReplacementAttorneyController',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'add' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/add',
                            'defaults' => array(
                                'action' => 'add',
                            ),
                        ),
                    ),
                    'edit' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/edit/:person_index',
                            'constraints' => array(
                                'person_index' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/delete/:person_index',
                            'constraints' => array(
                                'person_index' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                        ),
                    ),
                ),
            ),
            'what-is-my-role' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/what-is-my-role',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\WhatIsMyRoleController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'when-lpa-starts' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/when-lpa-starts',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\WhenLpaStartsController',
                        'action'     => 'index',
                    ),
                ),
            ),
            'when-replacement-attorney-step-in' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/when-replacement-attorney-step-in',
                    'defaults' => array(
                        'controller' => 'Authenticated\Lpa\WhenReplacementAttorneyStepInController',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
);
