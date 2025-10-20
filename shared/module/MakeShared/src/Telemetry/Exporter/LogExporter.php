<?php

declare(strict_types=1);

namespace MakeShared\Telemetry\Exporter;

use MakeShared\Logging\LoggerTrait;
use MakeShared\Telemetry\Segment;
use Psr\Log\LoggerAwareInterface;

/**
 * Export segments to a logger.
 * NB this ignores the Sampled flag on segments
 * and exports everything by default.
 */
class LogExporter implements ExporterInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public function export(Segment $segment): void
    {
        $this->getLogger()->debug('EXPORTING SEGMENT: ' . json_encode($segment));
    }
}
