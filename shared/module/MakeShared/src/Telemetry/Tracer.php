<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use MakeShared\Constants;
use MakeShared\Logging\SimpleLogger;
use MakeShared\Telemetry\Exporter\ExporterInterface;
use MakeShared\Telemetry\Exporter\LogExporter;
use MakeShared\Telemetry\Exporter\XrayExporter;
use MakeShared\Telemetry\Segment;

/**
 * Trace and export AWS X-Ray telemetry.
 *
 * The initial scaffolding is set up in MakeShared\Telemetry\Module.php.
 * This creates a root segment which other segments can hang off.
 * Most of the documentation is there.
 */
class Tracer
{
    private string $serviceName;

    private ExporterInterface $exporter;

    private SimpleLogger $logger;

    private ?Segment $rootSegment = null;

    private ?Segment $currentSegment = null;

    private array $segments = [];

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

    public function getExporter(): ExporterInterface
    {
        return $this->exporter;
    }

    /**
     * This is useful for passing a Parent key in the x-amz-trace-id
     * field when forwarding the trace ID to other components, e.g.
     * making an HTTP request from service-front to service-api.
     *
     * @return ?string ID of the currently-active segment, or null if not available
     */
    public function getCurrentSegmentId(): ?string
    {
        if (is_null($this->currentSegment)) {
            return null;
        }

        return $this->currentSegment->getId();
    }

    /**
     * Create the root segment; if we have no trace ID or trace ID without
     * a "Root" key, don't do anything (we can't trace these requests).
     *
     * @return ?Segment Root segment, or null if none was created
     */
    public function startRootSegment(?array $headers = null): ?Segment
    {
        if (is_null($headers)) {
            $headers = $_SERVER;
        }

        if ($this->started) {
            return null;
        }

        // format is like:
        // Root=1-63a17088-02b1471a787d91f21767c8f8;Parent=1234567891123456;Sampled=1
        $headerLine = $headers[Constants::X_TRACE_ID_HEADER_NAME] ?? '';
        parse_str(str_replace(';', '&', $headerLine), $traceHeader);

        if (!isset($traceHeader['Root'])) {
            return null;
        }

        // get the Parent part of the header if present, to attach the segments
        // from this tracer to segments which may have been created by other tracers
        // outside of the current request (e.g. if we're dealing with a request which
        // came from service-front to which we've attached a Parent segment ID)
        $parentSegmentId = $traceHeader['Parent'] ?? null;

        $this->rootSegment = new Segment($this->serviceName, $traceHeader['Root'], $parentSegmentId);
        $this->currentSegment = $this->rootSegment;
        $this->segments[$this->rootSegment->getId()] = $this->rootSegment;

        $this->started = true;

        return $this->rootSegment;
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
     *
     * @return ?Segment Created segment, or null if it could not be created
     */
    public function startSegment(string $name, array $attributes = []): ?Segment
    {
        if (!$this->started) {
            return null;
        }

        $child = $this->currentSegment->addChild($name);
        $this->segments[$child->getId()] = $child;
        $this->currentSegment = $child;

        return $this->currentSegment;
    }

    public function stopSegment(): void
    {
        if (!$this->started) {
            return;
        }

        $this->currentSegment->end();
        $this->currentSegment = $this->segments[$this->currentSegment->getParentSegmentId()];
    }

    public function stopRootSegment(): void
    {
        if (!$this->started) {
            return;
        }

        $this->rootSegment->end();

        $this->exporter->export($this->rootSegment);

        $this->started = false;
    }
}
