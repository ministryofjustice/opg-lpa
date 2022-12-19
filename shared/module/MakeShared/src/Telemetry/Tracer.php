<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use Laminas\Log\PsrLoggerAdapter;
use MakeShared\Constants;
use MakeShared\Logging\SimpleLogger;
use OpenTelemetry\Aws\Xray\Propagator;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\LoggerExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\Tracer as OTTracer;
use OpenTelemetry\SDK\Trace\TracerProvider as OTTracerProvider;
use RuntimeException;

/**
 * Factory for constructing an OpenTelemetry tracer which writes
 * to the standard Laminas log stream.
 *
 * Set up the initial scaffolding by calling start():
 *
 * $tracer = new Tracer();
 * $tracer->start();
 *
 * This creates and attaches a root span which other traces can hang off.
 *
 * To trace an individual piece of code, surround it like this:
 *
 * ;
 * // ******* code to be traced goes here *******
 * ;
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
 */
class Tracer
{
    private OTTracerProvider $tracerProvider;

    private OTTracer $tracer;

    private Propagator $propagator;

    private $root = null;
    private $rootScope = null;

    private bool $started = false;

    // we track the child spans so we can clean them up on stop()
    private $childSpans = [];

    public function __construct(
        OTTracerProvider $tracerProvider,
        OTTracer $tracer,
        Propagator $propagator
    ) {
        $this->tracerProvider = $tracerProvider;
        $this->tracer = $tracer;
        $this->propagator = $propagator;
    }

    /**
     * Factory method.
     *
     * @param array $config Expect a exporter.url property; if not set, a console
     * exporter is used by default.
     */
    public static function create(array $config)
    {
        $exporterUrl = $config['exporter']['url'];

        if (is_null($exporterUrl)) {
            $logger = new PsrLoggerAdapter(new SimpleLogger());
            $exporter = new LoggerExporter('service-api', $logger);
        } else {
            $transport = (new OtlpHttpTransportFactory())->create($exporterUrl, 'application/x-protobuf');
            $exporter = new SpanExporter($transport);
        }

        $tracerProvider = new OTTracerProvider(
            new SimpleSpanProcessor($exporter)
        );

        $tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');

        $propagator = new Propagator();

        return new Tracer($tracerProvider, $tracer, $propagator);
    }

    // Get a context object from the headers on the incoming
    // request, if present; this has to be constructed every time we get a span,
    // as the context is dependent on where we are embededded in the tree of spans.
    private function extractContext()
    {
        $headers = [];
        $headers[Propagator::AWSXRAY_TRACE_ID_HEADER] = $_SERVER[Constants::X_TRACE_ID_HEADER_NAME];
        return $this->propagator->extract($headers);
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
    public function startChild(string $name, array $attributes = []): ReadWriteSpanInterface
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
