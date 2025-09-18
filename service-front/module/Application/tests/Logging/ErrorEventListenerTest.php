<?php

namespace ApplicationTest\Logging;

use MakeShared\Logging\ErrorEventListener;
use Laminas\EventManager\EventManager;
use Laminas\Mvc\MvcEvent;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

final class ErrorEventListenerTest extends MockeryTestCase
{
    public function testMvcEventErrorHanding()
    {
        // stub event manager; mocking this out proved too circuitous
        $eventManager = new EventManager();

        $logger = Mockery::mock(LoggerInterface::class);

        $logger->shouldReceive('error')
               ->with(MvcEvent::EVENT_DISPATCH_ERROR, Mockery::type('array'))
               ->times(1);

        $logger->shouldReceive('error')
               ->with(MvcEvent::EVENT_RENDER_ERROR, Mockery::type('array'))
               ->times(1);

        $eventLogger = new ErrorEventListener();
        $eventLogger->setLogger($logger);
        $eventLogger->attach($eventManager);

        // dispatch events from the event manager and check that the logger
        // has error() called
        $eventManager->trigger(MvcEvent::EVENT_DISPATCH_ERROR);
        $eventManager->trigger(MvcEvent::EVENT_RENDER_ERROR);
    }
}
