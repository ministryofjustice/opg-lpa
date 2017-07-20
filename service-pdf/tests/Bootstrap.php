<?php

use Opg\Lpa\Pdf\Config\Config;

/**
 * Simple autoloader function to dynamically load
 * the required files as they are instantiated
 */
spl_autoload_register(function ($class) {

    //  Base directories where namespaced files reside
    $baseDirs = [
        __DIR__ . '/',
        __DIR__ . '/../src/',
    ];

    //  Loop through the base directories to try to find the requested class
    foreach ($baseDirs as $baseDir) {
        //  Replace the separators with directory separators in the relative class name, append and with .php
        $file = $baseDir . str_replace('\\', '/', $class) . '.php';

        // if the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

//  If it exists, hook into the composer autoload file too
$composerAutoloadFile = __DIR__ . '/../vendor/autoload.php';

if (file_exists($composerAutoloadFile)) {
    require_once $composerAutoloadFile;
}

//  Change the logging destination from a physical file to /dev/null while testing
$loggerConfig = Config::getInstance()['log'];
$loggerConfig['path'] = '/dev/null';

Config::getInstance()['log'] = $loggerConfig;
