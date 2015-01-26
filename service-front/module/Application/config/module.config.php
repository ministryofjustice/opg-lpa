<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            // ====== General =======
            'forgot-password' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/forgot-password',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\ForgotPasswordController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'index',
                    ),
                ),
            ),
            'guidance' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/guidance',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\GuidanceController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'index',
                    ),
                ),
            ),
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/home',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\HomeController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'index',
                    ),
                ),
            ),
            'enable-cookie' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/enable-cookie',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\HomeController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'enable-cookie',
                    ),
                ),
            ),
            'login' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/login',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\LoginController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'index',
                    ),
                ),
            ),
            'redirect' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\HomeController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'redirect',
                    ),
                ),
            ),
            'register' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/register',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\RegisterController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'index',
                    ),
                ),
            ),
            'reset-password' => array(
                'type'    => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route'    => '/reset-password/:password_reset_id',
                    'constraints' => array(
                        'password_reset_id' => '[a-zA-Z0-9]+',
                    ),
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\ForgotPasswordController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'reset-password',
                    ),
                ),
            ),
            'stats' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/stats',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\StatsController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'index',
                    ),
                ),
            ),
            'status' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/status',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\General\StatusController',
                        'controller' => 'ControllerFactory',
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
            ),
            
            // ====== Authenticated =======
            'admin-stats' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/admin/stats',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\Authenticated\AdminController',
                        'controller' => 'ControllerFactory',
                        'action'     => 'stats',
                    ),
                ),
            ),
            'postcode' => array(
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/postcode',
                    'defaults' => array(
                        'controllerName' => 'Application\Controller\Authenticated\PostcodeController',
                        'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\AboutYouController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'change-email-address' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/change-email-address',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\ChangeEmailAddressController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'change-password' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/change-password',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\ChangePasswordController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'dashboard' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/dashboard',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\DashboardController',
                                'controller' => 'ControllerFactory',
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
                                        'lpa-id' => '[a-zA-Z0-9]+',
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
                                        'lpa-id' => '[a-zA-Z0-9]+',
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
                                'controllerName' => 'Application\Controller\Authenticated\DeleteController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'logout' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/logout',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\LogoutController',
                                'controller' => 'ControllerFactory',
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
                        'lpa-id' => '[a-zA-Z0-9]+',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\ApplicantController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'certificate-provider' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/certificate-provider',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\CertificateProviderController',
                                'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\CompleteController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'correspondent' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/correspondent',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\CorrespondentController',
                                'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\CreatedController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'donor' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/donor',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\DonorController',
                                'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\DownloadController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'fee' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/fee',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\FeeController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'form-type' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/type',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\TypeController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'how-primary-attorneys-make-decision' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/how-primary-attorneys-make-decision',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'how-replacement-attorneys-make-decision' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/how-replacement-attorneys-make-decision',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'instructions' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/instructions',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\InstructionsController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'life-sustaining' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/life-sustaining',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\LifeSustainingController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'payment' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/payment',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\PaymentController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'payment-callback' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/payment-return',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\PaymentCallbackController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'people-to-notify' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/people-to-notify',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\PeopleToNotifyController',
                                'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\PrimaryAttorneyController',
                                'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\ReplacementAttorneyController',
                                'controller' => 'ControllerFactory',
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
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\WhatIsMyRoleController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'when-lpa-starts' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/when-lpa-starts',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\WhenLpaStartsController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'when-replacement-attorney-step-in' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/when-replacement-attorney-step-in',
                            'defaults' => array(
                                'controllerName' => 'Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController',
                                'controller' => 'ControllerFactory',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                ),
            ),
                
                
                
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
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
        'factories' => array(
            'ControllerFactory' => 'Application\ControllerFactory\ControllerFactory',
        ),
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
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
);
