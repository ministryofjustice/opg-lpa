<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-plugin-flashmessenger for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Plugin\FlashMessenger;

use Zend\Mvc\Plugin\FlashMessenger\View;
use Zend\ServiceManager\Factory\InvokableFactory;

class Module
{
    /**
     * Provide application configuration.
     *
     * Adds aliases and factories for the FlashMessenger plugin.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'controller_plugins' => [
                'aliases' => [
                    'flashmessenger' => FlashMessenger::class,
                    'flashMessenger' => FlashMessenger::class,
                    'FlashMessenger' => FlashMessenger::class,
                    'Zend\Mvc\Controller\Plugin\FlashMessenger' => FlashMessenger::class,
                ],
                'factories' => [
                    FlashMessenger::class => InvokableFactory::class,
                ],
            ],
            'view_helpers' => [
                'aliases' => [
                    'flashmessenger' => View\Helper\FlashMessenger::class,
                    'flashMessenger' => View\Helper\FlashMessenger::class,
                    'FlashMessenger' => View\Helper\FlashMessenger::class,

                ],
                'factories' => [
                    View\Helper\FlashMessenger::class => View\Helper\FlashMessengerFactory::class,
                    'zendviewhelperflashmessenger' => View\Helper\FlashMessengerFactory::class,
                ]
            ]
        ];
    }
}
