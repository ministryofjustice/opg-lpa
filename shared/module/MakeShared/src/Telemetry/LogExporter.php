<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use MakeShared\Logging\SimpleLoggerTrait;
use MakeShared\Telemetry\Segment;

class LogExporter implements ExporterInterface
{
    use SimpleLoggerTrait;

    public function export(Segment $segment): void
    {
        $this->getLogger()->debug(json_encode($segment));
    }
}
