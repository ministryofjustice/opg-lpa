<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return [

    /* ------------------------------------------------------------- */
    /* ------------ All routes are in module.routes.php ------------ */
    /* ------------------------------------------------------------- */

    'controllers' => [
        'factories' => [
            'SessionsController' => 'Application\Controller\Console\SessionsControllerFactory',
            'DynamoDbController' => 'Application\Controller\Console\DynamoDbControllerFactory',
        ],
        'abstract_factories' => [
            'Application\ControllerFactory\ControllerAbstractFactory'
        ],
    ],

    'service_manager' => [
        'abstract_factories' => [
            'Application\Model\Service\ServiceAbstractFactory',
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
        'aliases' => [
            'AdminService'                  => 'Application\Model\Service\Admin\Admin',
            'AnalyticsService'              => 'Application\Model\Service\Analytics\GoogleAnalyticsService',
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
        ],
    ],

    /*
    'translator' => [
        'locale' => 'en_US',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
    */

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

    'email_view_manager' => array(
        'template_path_stack' => array(
            'emails' => __DIR__ . '/../view/email',
        ),
    ),

    'view_helpers' => [
        'invokables' => [
            'pageHeaders'           => 'Application\View\Helper\PageHeaders',
            'elementGroupClass'     => 'Application\View\Helper\ElementGroupClass',
            'formElementErrors'     => 'Application\View\Helper\FormElementErrors',
            'formElementErrorsV2'   => 'Application\View\Helper\FormElementErrorsV2',
            'formErrorList'         => 'Application\View\Helper\FormErrorList',
            'formLinkedErrorList'   => 'Application\View\Helper\FormLinkedErrorList',
            'formLinkedErrorListV2' => 'Application\View\Helper\FormLinkedErrorListV2',
            'formErrorTextExchange' => 'Application\View\Helper\FormErrorTextExchange',
            'concatNames'           => 'Application\View\Helper\ConcatNames',
            'cellStyles'            => 'Application\View\Helper\CellStyles',
            'formatLpaId'           => 'Application\View\Helper\FormatLpaId',
            'ordinalSuffix'         => 'Application\View\Helper\OrdinalSuffix',
            'applicantNames'        => 'Application\View\Helper\ApplicantNames',
            'moneyFormat'           => 'Application\View\Helper\MoneyFormat',
            'formRadio'             => 'Application\Form\View\Helper\FormRadio',
            'formCheckbox'          => 'Application\Form\View\Helper\FormMultiCheckbox',
            'finalCheckAccessible'  => 'Application\View\Helper\FinalCheckAccessible',
        ],
        'factories' => [
            'accordion'     => 'Application\View\Helper\AccordionFactory',
            'accountInfo'   => 'Application\View\Helper\AccountInfoFactory',
            'routeName'     => 'Application\View\Helper\RouteNameFactory',
            'systemMessage' => 'Application\View\Helper\SystemMessageFactory',
        ],
    ],

    // Placeholder for console routes
    'console' => [
        'router' => [
            'routes' => [

                'dynamo-session-gc' => [
                    'type'    => 'simple',
                    'options' => [
                        'route'    => 'dynamo-session-gc',
                        'defaults' => [
                            'controller' => 'Console\SessionsController',
                            'action'     => 'gc'
                        ],
                    ],
                ],

                'dynamodb-init' => [
                    'type'    => 'simple',
                    'options' => [
                        'route'    => 'dynamodb-init',
                        'defaults' => [
                            'controller' => 'DynamoDbController',
                            'action'     => 'init'
                        ],
                    ],
                ],

            ],
        ],
    ],

];
