<?php

declare(strict_types=1);

namespace Application\Library\Telemetry;

use MakeShared\Logging\MetricRecorderInterface;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\MeterInterface;

final class OtelMetricRecorder implements MetricRecorderInterface
{
    /** @var array<string, CounterInterface> */
    private array $counters = [];

    public function __construct(private readonly MeterInterface $meter)
    {
    }

    #[\Override]
    public function record(string $event, array $attributes = []): void
    {
        $this->counters[$event] ??= $this->meter->createCounter($event, '{event}');
        $this->counters[$event]->add(1, $attributes);
    }
}
