<?php

if (!isset($argv[1])) {
    error_log('ERROR: index.xml coverage file must be specified');
    exit(1);
}

$xmlFile = $argv[1];
$minPercent = floatval($argv[2] ?? 100);

$xml = simplexml_load_file($xmlFile);
$xml->registerXPathNamespace('p', 'https://schema.phpunit.de/coverage/1.0');

$xpath = '/p:phpunit/p:project/p:directory[@name="/"]/p:totals/p:lines';
$lineCoverageElt = $xml->xpath($xpath)[0];

$coveragePercent = floatval($lineCoverageElt['percent']);

if ($coveragePercent < $minPercent) {
    error_log(
        "ERROR: Test coverage percentage {$coveragePercent}% is " .
        "less than required minimum {$minPercent}%"
    );

    exit(1);
}

echo(
    "SUCCESS: Test coverage percentage {$coveragePercent}% " .
    "is >= required minimum {$minPercent}\n"
);

exit(0);
