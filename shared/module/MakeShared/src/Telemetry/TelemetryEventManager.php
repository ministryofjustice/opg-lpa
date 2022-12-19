<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use Laminas\EventManager\EventManagerInterface;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;

class TelemetryEventManager
{
    public static EventManagerInterface $eventManager;

    public static function setEventManager(EventManagerInterface $eventManager)
    {
        self::$eventManager = $eventManager;
    }

    public static function triggerStart(string $spanName)
    {
        $event = new Event(Constants::TELEMETRY_START_CHILD, $spanName);
        self::$eventManager->triggerEvent($event);
    }

    public static function triggerStop(string $spanName)
    {
        $event = new Event(Constants::TELEMETRY_STOP_CHILD, $spanName);
        self::$eventManager->triggerEvent($event);
    }
}
