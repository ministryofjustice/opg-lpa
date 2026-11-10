<?php

declare(strict_types=1);

namespace ApplicationTest\Library\Telemetry;

use Application\Library\Telemetry\OtelMetricRecorder;
use ArrayObject;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricExporter\InMemoryExporter;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use PHPUnit\Framework\TestCase;

class OtelMetricRecorderTest extends TestCase
{
    public function testRecordCountsTheEventWithItsAttributes(): void
    {
        $exporter = new InMemoryExporter(new ArrayObject());
        $reader = new ExportingReader($exporter);
        $meterProvider = MeterProvider::builder()->addReader($reader)->build();

        $recorder = new OtelMetricRecorder($meterProvider->getMeter('test'));
        $recorder->record('event.user.created');
        $recorder->record('event.user.created');
        $recorder->record('event.shared_space.member.joined', ['is_admin' => true]);

        $reader->collect();
        $metrics = $exporter->collect();

        $this->assertCount(2, $metrics);

        $totals = [];
        foreach ($metrics as $metric) {
            $point = $metric->data->dataPoints[0];
            $totals[$metric->name] = [$point->value, $point->attributes->get('is_admin')];
        }

        $this->assertSame([2, null], $totals['event.user.created']);
        $this->assertSame([1, true], $totals['event.shared_space.member.joined']);
    }
}
