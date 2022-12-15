<?php

namespace Application\Library\Telemetry;

use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use RuntimeException;

class Tracer
{
    private static $tracer = null;
    private static $rootScope = null;

    public static $started = false;
    public static $root = null;

    public static function start()
    {
        if (is_null(self::$tracer)) {
            $tracerProvider = new TracerProvider(
                new SimpleSpanProcessor(
                    (new ConsoleSpanExporterFactory())->create()
                )
            );

            self::$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');
        }

        if (is_null(self::$root)) {
            self::$root = self::$tracer->spanBuilder('root')->startSpan();
        }

        if (is_null(self::$rootScope)) {
            self::$rootScope = self::$root->activate();
        }

        self::$started = true;
    }

    public static function stop()
    {
        if (!self::$started) {
            self::start();
        }

        self::$root->end();
        self::$rootScope->detach();

        self::$started = false;
    }
}
