<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use MakeShared\Telemetry\Segment;

interface ExporterInterface
{
    public function export(Segment $segment): void;
}
