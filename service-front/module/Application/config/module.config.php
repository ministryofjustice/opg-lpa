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
                        'controller' => 'Application\Controller\General\ForgotPassword',
                        'action'     => 'index',
                    ),
                ),
            ),
            'guidance' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/guidance',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Guidance',
                        'action'     => 'index',
                    ),
                ),
            ),
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/home',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Home',
                        'action'     => 'index',
                    ),
                ),
            ),
            'login' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/login',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Login',
                        'action'     => 'index',
                    ),
                ),
            ),
            'redirect' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Home',
                        'action'     => 'redirect',
                    ),
                ),
            ),
            'register' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/register',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Register',
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
                        'controller' => 'Application\Controller\General\ForgotPassword',
                        'action'     => 'reset-password',
                    ),
                ),
            ),
            'stats' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/stats',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Stats',
                        'action'     => 'index',
                    ),
                ),
            ),
            'status' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/status',
                    'defaults' => array(
                        'controller' => 'Application\Controller\General\Status',
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
                        'controller' => 'Application\Controller\Authenticated\Admin',
                        'action'     => 'stats',
                    ),
                ),
            ),
            'postcode' => array(
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/postcode',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Authenticated\Postcode',
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
                                'controller' => 'Application\Controller\Authenticated\AboutYou',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'change-email-address' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/change-email-address',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\ChangeEmailAddress',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'change-password' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/change-password',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\ChangePassword',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'dashboard' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/dashboard',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Dashboard',
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
                                'controller' => 'Application\Controller\Authenticated\Delete',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'logout' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/logout',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Logout',
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
                                'controller' => 'Application\Controller\Authenticated\Lpa\Applicant',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'certificate-provider' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/certificate-provider',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\CertificateProvider',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'complete' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/complete',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Complete',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'correspondant' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/correspondant',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Correspondant',
                                'action'     => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'default' => array(
                                'type'    => 'Segment',
                                'options' => array(
                                    'route'    => '/:action',
                                    'constraints' => array(
                                        'action'     => 'edit',
                                    ),
                                    'defaults' => array(
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
                                'controller' => 'Application\Controller\Authenticated\Lpa\Created',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'donor' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/donor',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Donor',
                                'action'     => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'default' => array(
                                'type'    => 'Segment',
                                'options' => array(
                                    'route'    => '/:action',
                                    'constraints' => array(
                                            'action'     => 'add|edit',
                                    ),
                                    'defaults' => array(
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'download' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/download',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Download',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'fee' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/fee',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Fee',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'how-primary-attorneys-make-decision' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/how-primary-attorneys-make-decision',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecision',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'how-replacement-attorneys-make-decision' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/how-replacement-attorneys-make-decision',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecision',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'instructions' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/instructions',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Instructions',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'life-sustaining' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/life-sustaining',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\LifeSustaining',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'online-payment-success' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/online-payment-success',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\OnlinePaymentSuccess',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'online-payment-unsuccessful' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/online-payment-unsuccessful',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\OnlinePaymentUnsuccessful',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'people-to-notify' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/people-to-notify',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\PeopleToNotify',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'primary-attorney' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/primary-attorney',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\PrimaryAttorney',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'replacement-attorney' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/replacement-attorney',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\ReplacementAttorney',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'form-type' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/type',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\Type',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'what-is-my-role' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/what-is-my-role',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\WhatIsMyRole',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'when-lpa-starts' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/when-lpa-starts',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\WhenLpaStarts',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'when-replacement-attorney-step-in' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route'    => '/when-replacement-attorney-step-in',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepIn',
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
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
            
            'Application\Controller\General\Home'           => 'Application\Controller\General\HomeController',
            'Application\Controller\General\Guidance'       => 'Application\Controller\General\GuidanceController',
            'Application\Controller\General\ForgotPassword'  => 'Application\Controller\General\ForgotPasswordController',
            'Application\Controller\General\Login'          => 'Application\Controller\General\LoginController',
            'Application\Controller\General\Register'       => 'Application\Controller\General\RegisterController',
            'Application\Controller\General\Stats'          => 'Application\Controller\General\StatsController',
            'Application\Controller\General\Status'         => 'Application\Controller\General\StatusController',
            
            'Application\Controller\Authenticated\AboutYou'           => 'Application\Controller\Authenticated\AboutYouController',
            'Application\Controller\Authenticated\Admin'                   => 'Application\Controller\Authenticated\AdminController',
            'Application\Controller\Authenticated\ChangeEmailAddress' => 'Application\Controller\Authenticated\ChangeEmailAddressController',
            'Application\Controller\Authenticated\ChangePassword'     => 'Application\Controller\Authenticated\ChangePasswordController',
            'Application\Controller\Authenticated\Dashboard'          => 'Application\Controller\Authenticated\DashboardController',
            'Application\Controller\Authenticated\Delete'             => 'Application\Controller\Authenticated\DeleteController',
            'Application\Controller\Authenticated\Logout'             => 'Application\Controller\Authenticated\LogoutController',
            'Application\Controller\Authenticated\Postcode'           => 'Application\Controller\Authenticated\PostcodeController',
             
            'Application\Controller\Authenticated\Lpa\Type'                   => 'Application\Controller\Authenticated\Lpa\TypeController',
            'Application\Controller\Authenticated\Lpa\Donor'                  => 'Application\Controller\Authenticated\Lpa\DonorController',
            'Application\Controller\Authenticated\Lpa\WhenLpaStarts'          => 'Application\Controller\Authenticated\Lpa\WhenLpaStartsController',
            'Application\Controller\Authenticated\Lpa\LifeSustaining'         => 'Application\Controller\Authenticated\Lpa\LifeSustainingController',
            'Application\Controller\Authenticated\Lpa\PrimaryAttorney'                     => 'Application\Controller\Authenticated\Lpa\PrimaryAttorneyController',
            'Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecision'     => 'Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController',
            'Application\Controller\Authenticated\Lpa\ReplacementAttorney'                 => 'Application\Controller\Authenticated\Lpa\ReplacementAttorneyController',
            'Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepIn'       => 'Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController',
            'Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecision' => 'Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController',
            'Application\Controller\Authenticated\Lpa\CertificateProvider'    => 'Application\Controller\Authenticated\Lpa\CertificateProviderController',
            'Application\Controller\Authenticated\Lpa\PeopleToNotify'         => 'Application\Controller\Authenticated\Lpa\PeopleToNotifyController',
            'Application\Controller\Authenticated\Lpa\Instructions'           => 'Application\Controller\Authenticated\Lpa\InstructionsController',
            'Application\Controller\Authenticated\Lpa\Created'                => 'Application\Controller\Authenticated\Lpa\CreatedController',
            'Application\Controller\Authenticated\Lpa\Applicant'              => 'Application\Controller\Authenticated\Lpa\ApplicantController',
            'Application\Controller\Authenticated\Lpa\Correspondant'          => 'Application\Controller\Authenticated\Lpa\CorrespondantController',
            'Application\Controller\Authenticated\Lpa\WhatIsMyRole'           => 'Application\Controller\Authenticated\Lpa\WhatIsMyRoleController',
            'Application\Controller\Authenticated\Lpa\Fee'                    => 'Application\Controller\Authenticated\Lpa\FeeController',
            'Application\Controller\Authenticated\Lpa\OnlinePaymentSuccess'               => 'Application\Controller\Authenticated\Lpa\OnlinePaymentSuccessController',
            'Application\Controller\Authenticated\Lpa\OnlinePaymentUnsuccessful'          => 'Application\Controller\Authenticated\Lpa\OnlinePaymentUnsuccessfulController',
            'Application\Controller\Authenticated\Lpa\Complete'               => 'Application\Controller\Authenticated\Lpa\CompleteController',
            'Application\Controller\Authenticated\Lpa\Clone'                  => 'Application\Controller\Authenticated\Lpa\CloneController',
            'Application\Controller\Authenticated\Lpa\Download'               => 'Application\Controller\Authenticated\Lpa\DownloadController',
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
