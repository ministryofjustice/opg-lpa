<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Application\Library\Telemetry\Tracer;
use Laminas\Mvc\Application;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

$tracer = Tracer::getInstance();
$tracer->start();

try {
    // This makes our life easier when dealing with paths. Everything is relative
    // to the application root now.
    chdir(dirname(__DIR__));

    // Run the application!
    Application::init(require_once(__DIR__ . '/../config/application.config.php'))->run();
} finally {
    $tracer->stop();
}
