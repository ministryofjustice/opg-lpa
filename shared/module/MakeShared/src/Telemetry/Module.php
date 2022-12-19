<?php

declare(strict_types=1);

namespace Telemetry;

use Laminas\Mvc\MvcEvent;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;
use MakeShared\Telemetry\TelemetryEventManager;
use MakeShared\Telemetry\Tracer;

class Module
{
    private Tracer $tracer;

    public function startChild(Event $e)
    {
        $this->tracer->startChild($e->getSpanName(), $e->getAttributes());
    }

    // when stopping a child, we ignore the event's attributes
    public function stopChild(Event $e)
    {
        $this->tracer->stopChild($e->getSpanName());
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $this->tracer = $event->getApplication()->getServiceManager()->get('TelemetryTracer');
        $this->tracer->start();

        $eventManager = $event->getApplication()->getEventManager();
        $eventManager->attach(Constants::TELEMETRY_START_CHILD, [$this, 'startChild']);
        $eventManager->attach(Constants::TELEMETRY_STOP_CHILD, [$this, 'stopChild']);
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish']);

        // gives us a globally-accessible Laminas event manager for triggering telemetry events
        TelemetryEventManager::setEventManager($eventManager);
    }

    public function onFinish(MvcEvent $event): void
    {
        $this->tracer->stop();
    }
}
