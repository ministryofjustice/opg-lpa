<?php

use Opg\Lpa\Pdf\Config\Config;

class ConfigSetUp
{
    /**
     * Universal config set up for unit tests
     */
    public static function init()
    {
        //  Set up any required config items
        $config = Config::getInstance();

        $serviceConfig = $config['service'];
        $serviceConfig['disable_draw_cross_lines'] = true;

        $config->offsetSet('service', $serviceConfig);

        //  Change the logging destination from a physical file to /dev/null while testing
        $loggerConfig = $config['log'];
        $loggerConfig['path'] = '/dev/null';

        $config->offsetSet('log', $loggerConfig);
    }
}
