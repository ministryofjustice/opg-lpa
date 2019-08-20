<?php

date_default_timezone_set('UTC');

error_reporting(E_ALL & ~ (E_DEPRECATED|E_USER_DEPRECATED));

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

    //  Strip out any leading "ApplicationTest" if present
    if (strpos($class, 'ApplicationTest\\') === 0) {
        $class = str_replace('ApplicationTest\\', '', $class);
    }

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
$composerAutoloadFile = __DIR__ . '/../../../vendor/autoload.php';

if (file_exists($composerAutoloadFile)) {
    require_once $composerAutoloadFile;
}

require __DIR__ . '/../../../vendor/ministryofjustice/opg-lpa-datamodels/tests/OpgTest/Lpa/DataModel/FixturesData.php';
