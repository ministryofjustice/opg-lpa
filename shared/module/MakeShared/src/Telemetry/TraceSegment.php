<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

class TraceSegment extends Segment
{
    public string $origin = 'AWS::ECS::Container';

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->traceId = $this->generateTraceId();
    }

    private function generateTraceId(): string
    {
        return '1-' . dechex(time()) . '-' . bin2hex(random_bytes(24 / 2));
    }

    public function jsonSerialize(): mixed
    {
        $serialized = parent::jsonSerialize();

        $serialized['trace_id'] = $this->traceId;
        $serialized['origin'] = $this->origin;

        return $serialized;
    }
}
