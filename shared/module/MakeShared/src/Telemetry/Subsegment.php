<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

class Subsegment extends Segment
{
    public ?string $parentId = null;
    public ?string $namespace = null;
    public bool $isIndependent = false;

    public function jsonSerialize(): mixed
    {
        $serialized = parent::jsonSerialize();

        if ($this->namespace) {
            $serialized['namespace'] = $this->namespace;
        }

        if ($this->isIndependent) {
            $serialized['type'] = 'subsegment';
            $serialized['trace_id'] = $this->traceId;
        }

        if ($this->parentId !== null) {
            $serialized['parent_id'] = $this->parentId;
        }

        return $serialized;
    }
}
