<?php

declare(strict_types=1);

namespace MakeShared\Logging;

interface MetricRecorderInterface
{
    /**
     * Counts one occurrence of an event.
     *
     * @param string $event One of the Metrics::* event constants
     * @param array<string, string|bool|int|float> $attributes Labels to split the metric by
     */
    public function record(string $event, array $attributes = []): void;
}
