<?php
namespace ZFConfigDump;

class Module {

    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array('namespaces' => array(
                __NAMESPACE__ => __DIR__,
            ))
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

}