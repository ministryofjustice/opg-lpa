<?php
/**
 * @link      http://github.com/zendframework/zend-mvc-console for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Console;

class Module
{
    /**
     * Provide default configuration.
     *
     * @param return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();
        return [
            'controller_plugins' => $provider->getPluginConfig(),
            'service_manager' => $provider->getDependencyConfig(),
            'console' => ['router' => ['routes' => []]],
        ];
    }
}
