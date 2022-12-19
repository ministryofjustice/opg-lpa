<?php

namespace MakeShared\Telemetry;

use Laminas\EventManager\Event as LaminasEvent;

class Event extends LaminasEvent
{
    private string $spanName;

    private array $attributes = [];

    public function __construct(string $name, string $spanName, array $attributes = [])
    {
        $this->setName($name);
        $this->setSpanName($spanName);
        $this->setAttributes($attributes);
    }

    public function setSpanName(string $spanName): void
    {
        $this->spanName = $spanName;
    }

    public function getSpanName(): string
    {
        return $this->spanName;
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
