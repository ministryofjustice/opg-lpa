<?php

namespace MakeShared\Telemetry;

use Laminas\Log\PsrLoggerAdapter;
use MakeShared\Logging\SimpleLoggerTrait;
use OpenTelemetry\SDK\Trace\SpanExporter\LoggerExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\Tracer as OTTracer;
use OpenTelemetry\SDK\Trace\TracerProvider as OTTracerProvider;
use RuntimeException;

/**
 * Factory for constructing an OpenTelemetry tracer which writes
 * to the standard Laminas log stream.
 *
 * index.php should set up the initial scaffolding by calling start():
 *
 * $tracer = Tracer::getInstance();
 * $tracer->start();
 *
 * This creates a single instance of the tracer per request, which
 * can then be used like a global throughout the code. It also creates
 * and attaches a root span which other traces can hang off.
 *
 * To trace an individual piece of code, surround it like this:
 *
 * $tracer = Tracer::getInstance();
 * $tracer->startChild('my.span.name');
 * // ******* code to be traced goes here *******
 * $tracer->stopChild('my.span.name');
 *
 * This attaches a child span to the root span set up by start().
 *
 * Tracing should then be stopped and cleaned up in index.php after the
 * main Laminas application run() method has completed:
 *
 * $tracer->stop();
 *
 * ($tracer should still be in scope in index.php)
 */
class Tracer
{
    use SimpleLoggerTrait;

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

        $logger = new PsrLoggerAdapter($this->getLogger());

        $this->tracerProvider = new OTTracerProvider(
            new SimpleSpanProcessor(
                new LoggerExporter('service-api', $logger)
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
