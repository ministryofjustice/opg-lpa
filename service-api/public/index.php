<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Laminas\Mvc\Application;

// This makes our life easier when dealing with paths. Everything is relative
// to the application root now.
chdir(dirname(__DIR__));

// Run the application!
Application::init(require_once(__DIR__ . '/../config/application.config.php'))->run();
