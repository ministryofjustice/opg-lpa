<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use MakeShared\Constants;
use MakeShared\Logging\SimpleLogger;
use MakeShared\Telemetry\ExporterInterface;
use MakeShared\Telemetry\LogExporter;
use MakeShared\Telemetry\Segment;
use MakeShared\Telemetry\Subsegment;
use MakeShared\Telemetry\TraceSegment;
use MakeShared\Telemetry\XrayExporter;

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
    private string $serviceName;

    private ExporterInterface $exporter;

    private SimpleLogger $logger;

    private ?Segment $rootSegment = null;

    private array $childSegments = [];

    private bool $started = false;

    public function __construct(string $serviceName, ExporterInterface $exporter, SimpleLogger $logger)
    {
        $this->serviceName = $serviceName;
        $this->exporter = $exporter;
        $this->logger = $logger;
    }

    /**
     * Factory method.
     *
     * @param array $config Expects exporter.host and exporter.url properties; if not set,
     * a console exporter is used by default.
     */
    public static function create(array $config = [])
    {
        $serviceName = $config['exporter']['serviceName'];
        $exporterHost = $config['exporter']['host'] ?? null;
        $exporterPort = $config['exporter']['port'] ?? null;

        if (is_null($exporterHost) || is_null($exporterPort)) {
            $exporter = new LogExporter();
        } else {
            $exporter = new XrayExporter($exporterHost, intval($exporterPort));
        }

        return new Tracer($serviceName, $exporter, new SimpleLogger());
    }

    private function createTrace(string $name): void
    {
        $this->rootSegment = new TraceSegment($name);
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $headerLine = $_SERVER[Constants::X_TRACE_ID_HEADER_NAME] ?? null;
        parse_str(str_replace(';', '&', $headerLine), $traceHeader);

        if (!isset($traceHeader['Root'])) {
            $this->createTrace($this->serviceName);
            return;
        }

        $segment = new Subsegment($this->serviceName);
        $segment->isIndependent = true;
        $segment->parentId = strval($traceHeader['Parent'] ?? null);
        $segment->traceId = strval($traceHeader['Root'] ?? null);

        $this->rootSegment = $segment;

        $this->started = true;
    }

    /**
     * Add a child span to the currently-active span (usually root).
     * The returned span can then have attributes set etc. as desired.
     *
     * TODO attributes
     * @param array $attributes Key/value pairs to set on the span, where each
     * key is a string and each value a "non-null string, boolean, floating point value,
     * integer, or an array of these values"
     * (see https://opentelemetry.io/docs/concepts/signals/traces/#attributes)
     */
    public function startChild(string $name, array $attributes = []): ?Segment
    {
        if (!$this->started) {
            return null;
        }

        if (is_null($this->rootSegment)) {
            $child = new Subsegment($name);
        } else {
            $child = $this->rootSegment->addChild($name);
        }

        $this->childSegments[$name] = $child;

        return $child;
    }

    public function stopChild(string $name): void
    {
        if (array_key_exists($name, $this->childSegments)) {
            $this->childSegments[$name]->end();
            unset($this->childSegments[$name]);
        }
    }

    public function stop(): void
    {
        if (!$this->started) {
            return;
        }

        // clean up any child spans which have been left open
        foreach ($this->childSegments as $childSpan) {
            $childSpan->end();
        }

        $this->childSegments = [];

        $this->rootSegment->end();

        $this->exporter->export($this->rootSegment);

        $this->started = false;
    }
}
