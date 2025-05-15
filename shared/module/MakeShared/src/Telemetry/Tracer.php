<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use MakeShared\Constants;
use MakeShared\Logging\LoggerTrait;
use MakeShared\Telemetry\Exporter\ExporterInterface;
use MakeShared\Telemetry\Exporter\LogExporter;
use MakeShared\Telemetry\Exporter\XrayExporter;
use MakeShared\Telemetry\Segment;
use mt_rand;
use mt_getrandmax;
use RuntimeException;

/**
 * Trace and export AWS X-Ray telemetry.
 *
 * The initial scaffolding is set up in MakeShared\Telemetry\Module.php.
 * This creates a root segment which other segments can hang off.
 * Most of the documentation is there.
 */
class Tracer
{
    use LoggerTrait;

    private string $serviceName;

    // fraction of requests we will sample; if a random number 0-1
    // is <= this value, we sample that request
    private float $requestsSampledFraction = 0.05;

    private ExporterInterface $exporter;

    private ?Segment $rootSegment = null;

    private ?Segment $currentSegment = null;

    private array $segments = [];

    private bool $started = false;

    public function __construct(
        string $serviceName,
        ExporterInterface $exporter,
        ?float $requestsSampledFraction = null,
    ) {
        $this->serviceName = $serviceName;
        $this->exporter = $exporter;

        if (!is_null($requestsSampledFraction)) {
            if ($requestsSampledFraction < 0.0 || $requestsSampledFraction > 1.0) {
                throw new RuntimeException('$requestsSampledFraction is outside range 0-1');
            }
            $this->requestsSampledFraction = $requestsSampledFraction;
        }
    }

    /**
     * Factory method.
     *
     * @param array $config Expects exporter.host and exporter.url properties; if not set,
     * a console exporter is used by default.
     * If requestsSampledFraction (a float from 0-1) is present in $config, it is used
     * to set the fraction of requests which will be sampled; otherwise, the default is used.
     */
    public static function create(array $config = [])
    {
        $requestsSampledFraction = $config['requestsSampledFraction'] ?? null;
        if (!is_null($requestsSampledFraction)) {
            $requestsSampledFraction = floatval($requestsSampledFraction);
        }

        $serviceName = $config['exporter']['serviceName'];
        $exporterHost = $config['exporter']['host'] ?? null;
        $exporterPort = $config['exporter']['port'] ?? null;

        if (is_null($exporterHost) || is_null($exporterPort)) {
            $exporter = new LogExporter();
        } else {
            $exporter = new XrayExporter($exporterHost, intval($exporterPort));
        }

        return new Tracer(
            $serviceName,
            $exporter,
            $requestsSampledFraction,
        );
    }

    public function getCurrentSegmentId(): ?string
    {
        if (is_null($this->currentSegment)) {
            return null;
        }

        return $this->currentSegment->getId();
    }

    public function getTraceHeaderToForward(): ?string
    {
        if (is_null($this->currentSegment)) {
            return null;
        }

        return sprintf(
            'Root=%s;Parent=%s;Sampled=%s',
            $this->currentSegment->traceId,
            $this->currentSegment->getId(),
            ($this->currentSegment->sampled ? '1' : '0'),
        );
    }

    public function getExporter(): ExporterInterface
    {
        return $this->exporter;
    }

    /**
     * Create the root segment; if we have no trace ID or trace ID without
     * a "Root" key, don't do anything (we can't trace these requests).
     *
     * @param ?array $headers Set to $_SERVER if not supplied
     * @param array $attributes Key/value pairs to append to segment
     *
     * @return ?Segment Root segment, or null if none was created
     */
    public function startRootSegment(?array $headers = null, array $attributes = []): ?Segment
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

        // set whether this segment should be sampled; if false, the XrayExporter
        // ignores the segment; if there is no Sampled flag in the header, we
        // randomly select whether the request should be sampled (which will be the
        // case when the request first comes in from the AWS load balancer)
        if (!isset($traceHeader['Sampled'])) {
            $sampled = (mt_rand() / mt_getrandmax()) <= $this->requestsSampledFraction;
        } else {
            $sampled = isset($traceHeader['Sampled']) && $traceHeader['Sampled'] === '1';
        }

        $this->rootSegment = new Segment(
            $this->serviceName,
            $traceHeader['Root'],
            $parentSegmentId,
            $sampled,
            $attributes,
        );

        $this->currentSegment = $this->rootSegment;
        $this->segments[$this->rootSegment->getId()] = $this->rootSegment;

        $this->started = true;

        return $this->rootSegment;
    }

    /**
     * Add a child span to the currently-active span (usually root).
     * The returned span can then have attributes set etc. as desired.
     *
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

        $child = $this->currentSegment->addChild($name, $attributes);
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

    public function getRootSegment(): ?Segment
    {
        return $this->rootSegment;
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
