<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use Laminas\EventManager\EventManagerInterface;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;

class TelemetryEventManager
{
    public static ?EventManagerInterface $eventManager = null;

    public static function setEventManager(EventManagerInterface $eventManager)
    {
        self::$eventManager = $eventManager;
    }

    public static function triggerStart(string $segmentName, array $attributes = [])
    {
        if (is_null(self::$eventManager)) {
            return;
        }

        $event = new Event(Constants::TELEMETRY_START_CHILD, $segmentName, $attributes);
        self::$eventManager->triggerEvent($event);
    }

    public static function triggerStop(string $segmentName)
    {
        if (is_null(self::$eventManager)) {
            return;
        }

        $event = new Event(Constants::TELEMETRY_STOP_CHILD, $segmentName);
        self::$eventManager->triggerEvent($event);
    }
}
