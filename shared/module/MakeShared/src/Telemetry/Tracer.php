<?php

namespace MakeShared\Telemetry;

use Laminas\Log\PsrLoggerAdapter;
use MakeShared\Constants;
use MakeShared\Logging\SimpleLoggerTrait;
use MakeShared\Logging\TraceIdProcessor;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Context;
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
 * (While the child spans could be started, returning a handle, then
 * stopped by calling method on the handle directly, the stopChild()
 * method means a caller doesn't need to know the OT Span API to be
 * able to start/stop spans. Where finer-grained control is required,
 * it's possible to interact directly with the Span.)
 *
 * This usually attaches a child span to the root span set up by start().
 * However, if you start a child span B while another child span A is
 * already running, the OT API will make B a child of A rather than root.
 * So, to attach children to the root span, stop any other child spans first.
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

    // we track the child spans so we can clean them up
    private $childSpans = [];

    private static $instance = null;

    // X-Amz-Trace-Id has format:
    // Root=1-5759e988-bd862e3fe1be46a994272793;Parent=53995c3f42cd8ad8;Sampled=1
    // (Parent and Sampled may be omitted).
    // This needs to be converted into the correct format for OpenTelemetry
    // (see https://www.w3.org/TR/trace-context/#traceparent-header)
    // which is what we're doing here.
    // Note: validation of the content of the header is handed off to
    // TraceContextPropagator, which ensures the characters fall within the
    // range defined in the spec.
    private function buildHeaders()
    {
        $traceIdHeaderLine = $_SERVER[Constants::X_TRACE_ID_HEADER_NAME] ?? null;

        // if no header is present, use the global current context
        if (is_null($traceIdHeaderLine)) {
            return Context::getCurrent();
        }

        $parsed = [];
        parse_str(str_replace(';', '&', $traceIdHeaderLine), $parsed);

        $version = '00';

        // trace ID must be 32 characters long with no hyphens; if not set,
        // provide an invalid trace ID as the default
        $traceId = $parsed['Root'] ?? str_repeat('0', 32);

        // strip the leading '1-' (presumably AWS's versioning?) and any hyphens
        $traceId = substr($traceId, 2);
        $traceId = str_replace('-', '', $traceId);

        // if Parent is not set, create an invalid parent string
        $parentId = $parsed['Parent'] ?? str_repeat('0', 16);

        // if Sampled=1, we use '01' as trace flags; otherwise, '00'
        $traceFlags = '0' . ($parsed['Sampled'] ?? '0');

        // if traceId or parentId are invalid, we'll just get the default current context
        $headers = [];
        $headers[TraceContextPropagator::TRACEPARENT] = "${version}-${traceId}-${parentId}-${traceFlags}";

        return $headers;
    }

    // Get a context object from the headers on the incoming
    // request, if present; this has to be constructed every time we get a span,
    // as the context is dependent on where we are embededded in the tree of spans
    private function extractContext()
    {
        $headers = $this->buildHeaders();
        return TraceContextPropagator::getInstance()->extract($headers);
    }

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

        $this->root = $this->tracer->spanBuilder('root')
            ->setParent($this->extractContext())
            ->startSpan();

        $this->rootScope = $this->root->activate();

        $this->started = true;
    }

    /**
     * Add a child span to the currently-active span (usually root).
     * This span can then have attributes set etc. as desired.
     */
    public function startChild($name)
    {
        if (!$this->started) {
            $this->start();
        }

        $span = $this->tracer->spanBuilder($name)
            ->setParent($this->extractContext())
            ->startSpan();

        $this->childSpans[$name] = $span;

        return $span;
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
