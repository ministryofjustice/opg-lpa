<?php

namespace ApplicationTest\Logging;

use Application\Logging\EventLogger;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\Mvc\MvcEvent;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\Logger\Logger;

class EventLoggerTest extends MockeryTestCase
{
    public function testMvcEventErrorHanding()
    {
        // stub event manager; mocking this out proved too circuitous
        $eventManager = new EventManager();

        $logger = Mockery::mock(Logger::class);

        $logger->shouldReceive('err')
               ->with(Mockery::on(function ($arg) {
                   return $arg['type'] == MvcEvent::EVENT_DISPATCH_ERROR &&
                       $arg['event'] instanceof Event;
               }))
               ->times(1);

        $logger->shouldReceive('err')
               ->with(Mockery::on(function ($arg) {
                   return $arg['type'] == MvcEvent::EVENT_RENDER_ERROR &&
                       $arg['event'] instanceof Event;
               }))
               ->times(1);

        $eventLogger = new EventLogger();
        $eventLogger->setLogger($logger);
        $eventLogger->attach($eventManager);

        // dispatch events from the event manager and check that the logger
        // has err() called
        $eventManager->trigger(MvcEvent::EVENT_DISPATCH_ERROR);
        $eventManager->trigger(MvcEvent::EVENT_RENDER_ERROR);
    }
}
