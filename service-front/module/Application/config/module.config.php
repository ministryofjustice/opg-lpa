<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

use Application\Handler\Factory\Lpa\CheckoutChequeHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutConfirmHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutIndexHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutPayHandlerFactory;
use Application\Handler\Factory\Lpa\CheckoutPayResponseHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandlerFactory;
use Application\Handler\Lpa\CheckoutChequeHandler;
use Application\Handler\Lpa\CheckoutConfirmHandler;
use Application\Handler\Lpa\CheckoutIndexHandler;
use Application\Handler\Lpa\CheckoutPayHandler;
use Application\Handler\Lpa\CheckoutPayResponseHandler;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandlerFactory;
use Application\Handler\Factory\Lpa\IndexHandlerFactory;
use Application\Handler\Factory\Lpa\PrimaryAttorneyHandlerFactory;
use Application\Handler\Lpa\IndexHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use Application\Handler\Lpa\PrimaryAttorneyHandler;
use Application\Model\Service\Lpa\ContinuationSheets;
use Application\Service\DateCheckViewModelHelper;
use Application\Service\Factory\DateCheckViewModelHelperFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigRenderer;

return [

    /* ------------------------------------------------------------- */
    /* ------------ All routes are in module.routes.php ------------ */
    /* ------------------------------------------------------------- */

    'controllers' => [
        'abstract_factories' => [
            'Application\ControllerFactory\ControllerAbstractFactory'
        ],
    ],

    'service_manager' => [
        'abstract_factories' => [
            'Application\Model\Service\ServiceAbstractFactory',
            'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
            'Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory',
        ],
        'factories' => [
            CheckoutIndexHandler::class => CheckoutIndexHandlerFactory::class,
            CheckoutChequeHandler::class => CheckoutChequeHandlerFactory::class,
            CheckoutConfirmHandler::class => CheckoutConfirmHandlerFactory::class,
            CheckoutPayHandler::class => CheckoutPayHandlerFactory::class,
            CheckoutPayResponseHandler::class => CheckoutPayResponseHandlerFactory::class,
            ContinuationSheets::class => InvokableFactory::class,
            DateCheckViewModelHelper::class => DateCheckViewModelHelperFactory::class,
            IndexHandler::class => IndexHandlerFactory::class,
            PrimaryAttorneyHandler::class => PrimaryAttorneyHandlerFactory::class,
            PrimaryAttorneyAddHandler::class => PrimaryAttorneyAddHandlerFactory::class,
            PrimaryAttorneyEditHandler::class => PrimaryAttorneyEditHandlerFactory::class,
            PrimaryAttorneyConfirmDeleteHandler::class => PrimaryAttorneyConfirmDeleteHandlerFactory::class,
            PrimaryAttorneyDeleteHandler::class => PrimaryAttorneyDeleteHandlerFactory::class,
            PrimaryAttorneyAddTrustHandler::class => PrimaryAttorneyAddTrustHandlerFactory::class,
        ],
        'aliases' => [
            'AdminService'                  => 'Application\Model\Service\Admin\Admin',
            'ApplicantService'              => 'Application\Model\Service\Lpa\Applicant',
            'Communication'                 => 'Application\Model\Service\Lpa\Communication',
            'Feedback'                      => 'Application\Model\Service\Feedback\Feedback',
            'Guidance'                      => 'Application\Model\Service\Guidance\Guidance',
            'LpaApplicationService'         => 'Application\Model\Service\Lpa\Application',
            'Metadata'                      => 'Application\Model\Service\Lpa\Metadata',
            'ReplacementAttorneyCleanup'    => 'Application\Model\Service\Lpa\ReplacementAttorneyCleanup',
            'SiteStatus'                    => 'Application\Model\Service\System\Status',
            'StatsService'                  => 'Application\Model\Service\Stats\Stats',
            'UserService'                   => 'Application\Model\Service\User\Details',
            TemplateRendererInterface::class => TwigRenderer::class,
        ],
    ],

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/500'               => __DIR__ . '/../view/error/500.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],

    'templates' => [
        'paths' => [
            'application' => [__DIR__ . '/../view/application'],
        ],
    ],

    'email_view_manager' => [
        'template_path_stack' => [
            'emails' => __DIR__ . '/../view/email',
        ],
    ],

    'view_helpers' => [
        'invokables' => [
            'formErrorTextExchange' => 'Application\View\Helper\FormErrorTextExchange',
            'formRadio'             => 'Application\Form\View\Helper\FormRadio',
            'formCheckbox'          => 'Application\Form\View\Helper\FormMultiCheckbox',
            // below helper has been raised with laminas-form for an upstream change
            // https://github.com/laminas/laminas-form/issues/78
            'formtext'              => 'Application\Form\View\Helper\FormText',
        ],
        'factories' => [
            'routeName'             => 'Application\View\Helper\RouteNameFactory',
        ],
    ],

    'templates' => [
        'paths' => [
            'application' => [__DIR__ . '/../view/application'],
        ],
    ]

];
