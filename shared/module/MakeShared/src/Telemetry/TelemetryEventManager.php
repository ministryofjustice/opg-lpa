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

    // start a new segment; this will be a child of the currently-active segment
    // and will become the new current segment
    public static function triggerStart(string $segmentName, array $attributes = [])
    {
        if (is_null(self::$eventManager)) {
            return;
        }

        $event = new Event(Constants::TELEMETRY_START_SEGMENT, $segmentName, $attributes);
        self::$eventManager->triggerEvent($event);
    }

    // stop the current segment
    public static function triggerStop()
    {
        if (is_null(self::$eventManager)) {
            return;
        }

        $event = new Event(Constants::TELEMETRY_STOP_SEGMENT);
        self::$eventManager->triggerEvent($event);
    }
}
