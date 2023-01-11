<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use Laminas\Log\PsrLoggerAdapter;
use MakeShared\Constants;
use MakeShared\Logging\SimpleLogger;
use MakeShared\Telemetry\UdpTransport;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\LoggerExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\Tracer as OTTracer;
use OpenTelemetry\SDK\Trace\TracerProvider as OTTracerProvider;
use RuntimeException;

/**
 * Wrapper around an OpenTelemetry tracer which exports telemetry
 * data in a configurable way.
 *
 * The initial scaffolding is set up in MakeShared\Telemetry\Module.php.
 * This creates and attaches a root span which other traces can hang off.
 * Most of the documentation is there.
 */
class Tracer
{
    private OTTracerProvider $tracerProvider;

    private OTTracer $tracer;

    private SimpleLogger $logger;

    private $root = null;
    private $rootScope = null;

    private bool $started = false;

    // we track the child spans so we can clean them up on stop()
    private $childSpans = [];

    public function __construct(
        OTTracerProvider $tracerProvider,
        OTTracer $tracer,
        SimpleLogger $logger
    ) {
        $this->tracerProvider = $tracerProvider;
        $this->tracer = $tracer;
        $this->logger = $logger;
    }

    /**
     * Factory method.
     *
     * @param array $config Expects a exporter.url property; if not set, a console
     * exporter is used by default.
     */
    public static function create(array $config = [])
    {
        $logger = new SimpleLogger();
        $exporterUrl = $config['exporter']['url'] ?? null;

        if (is_null($exporterUrl)) {
            $wrappedLogger = new PsrLoggerAdapter($logger);
            $exporter = new LoggerExporter('service-api', $wrappedLogger);
        } else {
            $logger->info("Telemetry will be sent over UDP to ${exporterUrl}");
            $transport = new UdpTransport($exporterUrl);
            $exporter = new SpanExporter($transport);
        }

        $tracerProvider = new OTTracerProvider(
            new SimpleSpanProcessor($exporter)
        );

        $tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');

        return new Tracer($tracerProvider, $tracer, $logger);
    }

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

        // if no header is present, use the current context
        if (is_null($traceIdHeaderLine)) {
            return Context::getCurrent();
        }

        $parsed = [];
        parse_str(str_replace(';', '&', $traceIdHeaderLine), $parsed);

        $version = '00';

        // trace ID must be 32 characters long with no hyphens; if not set,
        // provide a random trace ID as the default
        $traceId = $parsed['Root'] ?? bin2hex(random_bytes(16));

        // strip the leading '1-' (presumably AWS's versioning?) and any hyphens
        $traceId = substr($traceId, 2);
        $traceId = str_replace('-', '', $traceId);

        // if Parent is not set, generate one (this is the span ID and is required)
        $spanId = $parsed['Parent'] ?? bin2hex(random_bytes(8));

        // if Sampled=1, we use '01' as trace flags; otherwise, '00'
        $traceFlags = '0' . ($parsed['Sampled'] ?? '0');

        // if traceId or spanId are invalid, we'll just get the default current context here
        // and new trace and span IDs will be generated, replacing our invalid ones
        $headers = [
            TraceContextPropagator::TRACEPARENT => "${version}-${traceId}-${spanId}-${traceFlags}"
        ];

        return $headers;
    }

    // Get a context object from the headers on the incoming
    // request, if present; this has to be constructed every time we get a span,
    // as the context is dependent on where we are embededded in the tree of spans.
    private function extractContext()
    {
        $headers = $this->buildHeaders();
        return TraceContextPropagator::getInstance()->extract($headers);
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->root = $this->tracer->spanBuilder('root')
            ->setParent($this->extractContext())
            ->startSpan();

        $this->rootScope = $this->root->activate();

        $this->started = true;
    }

    /**
     * Add a child span to the currently-active span (usually root).
     * The returned span can then have attributes set etc. as desired.
     *
     * @param array $attributes Key/value pairs to set on the span, where each
     * key is a string and each value a "non-null string, boolean, floating point value,
     * integer, or an array of these values"
     * (see https://opentelemetry.io/docs/concepts/signals/traces/#attributes)
     */
    public function startChild(string $name, array $attributes = []): SpanInterface
    {
        if (!$this->started) {
            $this->start();
        }

        $span = $this->tracer->spanBuilder($name)
            ->setParent($this->extractContext())
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $this->childSpans[$name] = $span;

        return $span;
    }

    public function stopChild(string $name): void
    {
        if (array_key_exists($name, $this->childSpans)) {
            $this->childSpans[$name]->end();
            unset($this->childSpans[$name]);
        }
    }

    public function stop(): void
    {
        if (!$this->started) {
            return;
        }

        // clean up any child spans which have been left running
        foreach ($this->childSpans as $childSpan) {
            $childSpan->end();
        }

        $this->childSpans = [];

        $this->rootScope->detach();
        $this->root->end();
        $this->tracerProvider->shutdown();

        $this->started = false;
    }
}
