<?php

namespace MakeShared\Telemetry;

use Laminas\EventManager\Event as LaminasEvent;

class Event extends LaminasEvent
{
    private string $spanName;

    public function __construct(string $name, string $spanName)
    {
        $this->setName($name);
        $this->setSpanName($spanName);
    }

    public function setSpanName(string $spanName): void
    {
        $this->spanName = $spanName;
    }

    public function getSpanName(): string
    {
        return $this->spanName;
    }
}
