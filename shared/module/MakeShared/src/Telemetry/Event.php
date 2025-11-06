<?php

namespace MakeShared\Telemetry;

use Laminas\EventManager\Event as LaminasEvent;

/** @psalm-suppress MissingTemplateParam */
class Event extends LaminasEvent
{
    private ?string $segmentName;

    private array $attributes = [];

    public function __construct($eventName, $segmentName = null, $attributes = [])
    {
        $this->setName($eventName);

        $this->setSegmentName($segmentName);

        $this->setAttributes($attributes);
    }

    public function setSegmentName(?string $segmentName): void
    {
        $this->segmentName = $segmentName;
    }

    public function getSegmentName(): ?string
    {
        return $this->segmentName;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
