<?php

namespace MakeSharedTest\Telemetry;

use Laminas\EventManager\EventManagerInterface;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;
use MakeShared\Telemetry\TelemetryEventManager;
use Mockery;
use PHPUnit\Framework\TestCase;

class TelemetryEventManagerTest extends TestCase
{
    private EventManagerInterface $eventManager;

    public function testTriggerNoEventManager()
    {
        $this->assertNull(TelemetryEventManager::triggerStart('foo'));
        $this->assertNull(TelemetryEventManager::triggerStop());
    }

    public function testTriggerStart()
    {
        $this->eventManager = Mockery::mock(EventManagerInterface::class);
        TelemetryEventManager::setEventManager($this->eventManager);

        $this->eventManager->shouldReceive('triggerEvent')->withArgs(function ($event) {
            return $event->getName() === Constants::TELEMETRY_START_SEGMENT &&
                $event->getSegmentName() === 'testTelemetryEventStart' &&
                is_a($event, Event::class);
        })->once();

        $event = TelemetryEventManager::triggerStart('testTelemetryEventStart');

        $this->assertEquals([], $event->getAttributes());
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testTriggerStop()
    {
        $this->eventManager = Mockery::mock(EventManagerInterface::class);
        TelemetryEventManager::setEventManager($this->eventManager);

        $this->eventManager->shouldReceive('triggerEvent')->withArgs(function ($event) {
            return $event->getName() === Constants::TELEMETRY_STOP_SEGMENT &&
                is_a($event, Event::class);
        })->once();

        $event = TelemetryEventManager::triggerStop();

        $this->assertEquals([], $event->getAttributes());
        $this->assertInstanceOf(Event::class, $event);
    }
}
