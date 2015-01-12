<?php

if (class_exists('PHPUnit_Runner_Version', true)) {

    $phpUnitVersion = PHPUnit_Runner_Version::id();
    if ('@package_version@' !== $phpUnitVersion 
        && version_compare($phpUnitVersion, '3.6.0', '<')) {

        echo 'This version of PHPUnit ('.PHPUnit_Runner_Version::id().') is not supported in Zend Framework 2.x unit tests.'.PHP_EOL;
        exit(1);
    }

    unset($phpUnitVersion);
}

chdir(dirname(__DIR__));
date_default_timezone_set('Europe/London');
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', true);
ini_set('log_errors',    false);

require 'init_autoloader.php';
$configuration = require 'config/application.config.php';
Zend\Mvc\Application::init($configuration);
