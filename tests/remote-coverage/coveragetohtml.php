<?php

$baseDir = '/app';

include $baseDir . '/vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as Html;

$allCoverage = require $baseDir . '/build/remote-coverage-php/merged.cov';

$filter = new Filter();
$filter->includeDirectory($baseDir . '/module/Application/src');
$coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
$coverage->merge($allCoverage);

$htmlDir = $baseDir . '/build/remote-coverage-html/';
echo "Writing HTML remote coverage report to $htmlDir";
(new Html())->process($coverage, $htmlDir);
