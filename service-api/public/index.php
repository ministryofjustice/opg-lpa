<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Application\Library\Telemetry\Tracer;
use Laminas\Mvc\Application;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

Tracer::start();

try {
    // This makes our life easier when dealing with paths. Everything is relative
    // to the application root now.
    chdir(dirname(__DIR__));

    // Run the application!
    Application::init(require_once(__DIR__ . '/../config/application.config.php'))->run();
} finally {
    Tracer::stop();
}

/*
try {
    $span1 = $tracer->spanBuilder('foo')->startSpan();
    $scope = $span1->activate();
    try {
        $span2 = $tracer->spanBuilder('bar')->startSpan();
        echo 'OpenTelemetry welcomes PHP' . PHP_EOL;
    } finally {
        $span2->end();
    }
} finally {
    $span1->end();
    $scope->detach();
}
*/
