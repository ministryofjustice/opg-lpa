<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use JsonSerializable;
use Telemetry\Tracer;

abstract class Segment implements JsonSerializable
{
    public string $id;
    public float $start;
    public ?float $end = null;

    public string $traceId;

    /** @var Subsegment[] $children */
    public array $children = [];

    public function __construct(public string $name, ?Segment $rootSegment = null)
    {
        $this->id = bin2hex(random_bytes(16 / 2));
        $this->start = microtime(true);
    }

    public function addChild(string $name): Subsegment
    {
        $child = new Subsegment($name);
        $this->children[] = $child;
        return $child;
    }

    public function end(): void
    {
        $this->end = microtime(true);
    }

    public function jsonSerialize(): mixed
    {
        $serialized = [
            "name" => $this->name,
            "id" => $this->id,
            "start_time" => $this->start,
            "end_time" => $this->end,
        ];

        if (count($this->children)) {
            $serialized["subsegments"] = $this->children;
        }

        return $serialized;
    }
}
