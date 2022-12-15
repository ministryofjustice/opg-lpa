<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Laminas\Mvc\Application;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

$tracerProvider = new TracerProvider(
    new SimpleSpanProcessor(
        (new ConsoleSpanExporterFactory())->create()
    )
);

$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');
$rootSpan = $tracer->spanBuilder('root')->startSpan();
$rootScope = $rootSpan->activate();

try {
    // This makes our life easier when dealing with paths. Everything is relative
    // to the application root now.
    chdir(dirname(__DIR__));

    // Run the application!
    $app = Application::init(require_once(__DIR__ . '/../config/application.config.php'));

    $app->run();
} finally {
    $rootSpan->end();
    $rootScope->detach();
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
