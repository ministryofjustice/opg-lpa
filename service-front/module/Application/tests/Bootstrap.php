<?php

date_default_timezone_set('UTC');

/**
 * Simple autoloader function to dynamically load
 * the required files as they are instantiated
 */
spl_autoload_register(function ($class) {

    //  Base directories where namespaced files reside
    $baseDirs = [
        __DIR__ . '/',
        __DIR__ . '/../src/',
        __DIR__ . '/../../../../shared/logging/module/MakeLogger/src/'
    ];

    //  Strip out any leading "ApplicationTest" if present
    if (strpos($class, 'ApplicationTest\\') === 0) {
        $class = str_replace('ApplicationTest\\', '', $class);
    }

    // Replace MakeLogger\Logging with Logging; this is so we can load
    // files from the top-level shared
    if (strpos($class, 'MakeLogger\\Logging') !== -1) {
        $class = str_replace('MakeLogger\\Logging', '\\Logging', $class);
    }

    //  Loop through the base directories to try to find the requested class
    foreach ($baseDirs as $baseDir) {
        // Replace the separators with directory separators in the relative class name
        // and append ".php"

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
require __DIR__ . '/ControllerFactory/NonDispatchableController.php';
