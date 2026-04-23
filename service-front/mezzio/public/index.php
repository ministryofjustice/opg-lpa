<?php

declare(strict_types=1);

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

// Replaces the register_shutdown_function in Module::onBootstrap().
// The ErrorHandler middleware handles exceptions, but fatal PHP errors
// (E_ERROR) bypass it entirely — this is the last line of defence.
register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (($error['type'] ?? null) === E_ERROR) {
        // The fatal error will have been written to the error log already.
        echo 'An unknown server error has occurred.';
    }
});

/**
 * Self-called anonymous function that creates its own scope and keeps the global namespace clean.
 */
(function () {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = require 'config/container.php';

    /** @var \Mezzio\Application $app */
    $app = $container->get(\Mezzio\Application::class);
    $factory = $container->get(\Mezzio\MiddlewareFactory::class);

    // Execute programmatic/declarative middleware pipeline and routing
    // configuration statements
    (require 'config/pipeline.php')($app, $factory, $container);
    (require 'config/routes.php')($app, $factory, $container);

    $app->run();
})();
