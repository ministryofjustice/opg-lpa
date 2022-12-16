<?php

namespace Application\Library\Telemetry;

use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\Tracer as OTTracer;
use OpenTelemetry\SDK\Trace\TracerProvider as OTTracerProvider;
use RuntimeException;

class Tracer
{
    /** @var OTTraceProvider */
    private $tracerProvider = null;

    /** @var OTTracer */
    private $tracer = null;

    private $root = null;
    private $rootScope = null;
    private $started = false;
    private $childSpans = [];

    private static $instance = null;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Tracer();
        }

        return self::$instance;
    }

    public function start()
    {
        if ($this->started) {
            return;
        }

        $this->tracerProvider = new OTTracerProvider(
            new SimpleSpanProcessor(
                (new ConsoleSpanExporterFactory())->create()
            )
        );

        $this->tracer = $this->tracerProvider->getTracer('io.opentelemetry.contrib.php');

        $this->root = $this->tracer->spanBuilder('root')->startSpan();

        $this->rootScope = $this->root->activate();

        $this->started = true;
    }

    public function startChild($name)
    {
        if (!$this->started) {
            $this->start();
        }

        $this->childSpans[$name] = $this->tracer->spanBuilder($name)->startSpan();
    }

    public function stopChild($name)
    {
        if (array_key_exists($name, $this->childSpans)) {
            $this->childSpans[$name]->end();
        }
    }

    public function stop()
    {
        if (!$this->started) {
            $this->start();
        }

        $this->rootScope->detach();
        $this->root->end();
        $this->tracerProvider->shutdown();

        $this->started = false;
    }
}
