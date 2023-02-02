<?php

declare(strict_types=1);

namespace MakeShared\Telemetry\Exporter;

use MakeShared\Logging\SimpleLoggerTrait;
use MakeShared\Telemetry\Segment;

/**
 * Export segments to a logger.
 * NB this ignores the Sampled flag on segments
 * and exports everything by default.
 */
class LogExporter implements ExporterInterface
{
    use SimpleLoggerTrait;

    public function export(Segment $segment): void
    {
        $this->getLogger()->debug(json_encode($segment));
    }
}
