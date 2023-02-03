<?php

namespace MakeSharedTest\Telemetry;

use Laminas\EventManager\EventManager;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;
use MakeShared\Telemetry\Module;
use MakeShared\Telemetry\TelemetryEventManager;
use MakeShared\Telemetry\Tracer;
use Mockery;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    // because Module manipulates static instance variables,
    // we need to make sure the TelemetryEventManager is reset
    // after this test
    public function tearDown(): void
    {
        TelemetryEventManager::setEventManager(null);
    }

    // test onBootstrap() and onFinish(), to ensure handlers are
    // correctly attached/detached at these points in the lifecycle
    public function testLifecycle()
    {
        $module = new Module();

        $tracer = Mockery::mock(Tracer::class);
        $tracer->shouldReceive('startRootSegment');
        $tracer->shouldReceive('startSegment');
        $tracer->shouldReceive('stopSegment');
        $tracer->shouldReceive('stopRootSegment');
        $tracer->shouldReceive('getRootSegment');

        $serviceManager = Mockery::mock(ServiceManager::class);
        $serviceManager->shouldReceive('get')
            ->with('TelemetryTracer')
            ->andReturn($tracer);

        $eventManager = Mockery::mock(EventManager::class);
        $eventManager->shouldReceive('attach')->with(
            Constants::TELEMETRY_START_SEGMENT,
            [$module, 'startSegment']
        );
        $eventManager->shouldReceive('attach')->with(
            Constants::TELEMETRY_STOP_SEGMENT,
            [$module, 'stopSegment']
        );
        $eventManager->shouldReceive('attach')->with(
            MvcEvent::EVENT_FINISH,
            [$module, 'onFinish']
        );

        $app = Mockery::mock(Application::class);
        $app->shouldReceive('getServiceManager')->andReturn($serviceManager);
        $app->shouldReceive('getEventManager')->andReturn($eventManager);

        $bootstrapEvent = Mockery::mock(MvcEvent::class);
        $bootstrapEvent->shouldReceive('getApplication')->andReturn($app);

        $telemetryStartEvent = Mockery::mock(Event::class);
        $telemetryStartEvent->shouldReceive('getSegmentName')->andReturn('foo');
        $telemetryStartEvent->shouldReceive('getAttributes')->andReturn([]);

        $telemetryStopEvent = Mockery::mock(Event::class);

        $finishEvent = Mockery::mock(MvcEvent::class);

        $module->onBootstrap($bootstrapEvent);
        $module->startSegment($telemetryStartEvent);
        $module->stopSegment($telemetryStopEvent);
        $module->onFinish($finishEvent);

        $this->assertTrue(true);
    }
}
