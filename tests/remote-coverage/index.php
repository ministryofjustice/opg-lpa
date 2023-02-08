<?php

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';

use Laminas\Mvc\Application;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as Html;
use SebastianBergmann\CodeCoverage\Report\PHP;

// Define directories containing the code you want to cover
$basePath = __DIR__ . '/..';

$filter = new Filter();
$filter->includeDirectory($basePath . '/module/Application/src');
$coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

$coverage->start('service-front');

// Run the application!
Application::init(require __DIR__ . '/../config/application.config.php')->run();

$coverage->stop();

$allCoverageFile = $basePath . '/build/remote-coverage-php/merged.cov';

$allCoverage = $coverage;
if (file_exists($allCoverageFile)) {
    $allCoverage = require $allCoverageFile;
    $allCoverage->merge($coverage);
}

(new PHP())->process($allCoverage, $allCoverageFile);
