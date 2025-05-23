<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use JsonSerializable;
use Telemetry\Tracer;

// see https://docs.aws.amazon.com/xray/latest/devguide/xray-api-sendingdata.html
class Segment implements JsonSerializable
{
    private string $segmentName;

    private float $start;
    private ?float $end = null;

    // segment ID, e.g. 70de5b6f19ff9a0a, generated by constructor
    private string $id;

    // from the x-amz-trace-id header on the initiating request
    // e.g. 1-581cf771-a006649127e371903a2de979
    public string $traceId;

    /** @var Segment[] $children */
    private array $children = [];

    public bool $sampled = true;

    // array of name => value pairs, where value implements JsonSerializable
    // Note that the http attribute is privileged, and can be set at the
    // top level of the segment; however, if you need to add metadata
    // or annotations, these can be set by using the respective key
    // and a value which represents the annotation/metadata, e.g.
    //   $segment->setAttribute('annotations', ['table' => 'users'])
    // or via an event:
    //   TelemetryEventManager::triggerStart(
    //       'DbWrapper.select',
    //       ['annotations' => ['table' => $tableName]]
    //   );
    /** @var array $attributes */
    private array $attributes = [];

    // parent segment ID; set for subsegments, and on the root segment
    // if a Parent was specified in the x-amz-trace-id header
    private ?string $parentSegmentId = null;

    public function __construct(
        string $segmentName,
        string $traceId,
        ?string $parentSegmentId = null,
        bool $sampled = true,
        array $attributes = [],
    ) {
        $this->segmentName = $segmentName;
        $this->traceId = $traceId;
        $this->parentSegmentId = $parentSegmentId;
        $this->sampled = $sampled;
        $this->attributes = $attributes;

        $this->id = bin2hex(random_bytes(16 / 2));
        $this->start = microtime(true);
    }

    public function addChild(string $segmentName, array $attributes = []): Segment
    {
        $child = new Segment(
            $segmentName,
            $this->traceId,
            $this->id,
            $this->sampled,
            $attributes,
        );

        $this->children[] = $child;

        return $child;
    }

    public function end(): void
    {
        if ($this->hasEnded()) {
            return;
        }

        foreach ($this->children as $child) {
            if (!$child->hasEnded()) {
                $child->end();
            }
        }

        $this->end = microtime(true);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParentSegmentId(): ?string
    {
        return $this->parentSegmentId;
    }

    public function hasEnded(): bool
    {
        return !is_null($this->end);
    }

    /**
     * Set an attribute on the segment.
     * This is mostly used to set the "http" attribute once the
     * request/response cycle is finished.
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function jsonSerialize(): mixed
    {
        $serialized = [
            'name' => $this->segmentName,
            'id' => $this->id,
            'start_time' => $this->start,
            'end_time' => $this->end,
            'trace_id' => $this->traceId,
        ];

        if (count($this->attributes) > 0) {
            $serialized = array_merge($serialized, $this->attributes);
        }

        if (!is_null($this->parentSegmentId)) {
            $serialized['parent_id'] = $this->parentSegmentId;
            $serialized['type'] = 'subsegment';
        }

        if (count($this->children) > 0) {
            $serialized['subsegments'] = $this->children;
        }

        return $serialized;
    }
}
