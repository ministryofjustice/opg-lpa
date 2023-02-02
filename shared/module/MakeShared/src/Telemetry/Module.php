<?php

declare(strict_types=1);

namespace MakeShared\Telemetry;

use Laminas\Mvc\MvcEvent;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;
use MakeShared\Telemetry\TelemetryEventManager;
use MakeShared\Telemetry\Tracer;

/**
 * Set up event handlers for capturing trace events triggered in the code.
 * This approach was used rather than singletons so that the tracer
 * instance can be configured like any other Laminas service. To start a new
 * segment in code, use the TelemetryEventManager to trigger start/stop child
 * events.
 *
 * CONFIGURATION
 *
 * To incorporate telemetry into your Laminas application, you'll need to load
 * this module in application.config.php:
 *
 * return array(
 *     'modules' => array(
 *         // ... other modules here ...
 *
 *         'MakeShared\Telemetry',
 *     ),
 *     'module_listener_options' => array(
 *         'module_paths' => array(
 *              './module',
 *              './vendor',
 *
 *              // or whatever the path is to the directory; I ended up doing this
 *              // because the autloader refused to find this directory
 *              'Telemetry' => __DIR__ . '/../../shared/module/MakeShared/src/Telemetry',
 *          ),
 *      ),
 *
 *      // ... other application config
 * );
 *
 * You will also need to add a Tracer resource to your application module,
 * called TelemetryTracer:
 *
 *    class Module
 *    {
 *        public function getServiceConfig()
 *        {
 *            return [
 *                // ... other factories ...
 *
 *                'factories' => [
 *                    'TelemetryTracer' => function ($sm) {
 *                        $telemetryConfig = $sm->get('config')['telemetry'];
 *                        return Tracer::create($telemetryConfig);
 *                    },
 *                ]
 *            ];
 *        }
 *    }
 *
 * Note that here we pass in some config which contains exporter.host and
 * export.port properties. These point to the UDP port of an aws-otel-collector
 * sidecar, typically on port 2000.
 *
 * HOW TRACING WORKS
 *
 * The $this->tracer->startRootSegment() call in the Module below sets up the
 * root segment.
 *
 * Then, to trace an individual piece of code, surround it like this:
 *
 *     use MakeShared\Telemetry\TelemetryEventManager;
 *
 *     TelemetryEventManager::triggerStart('DbWrapper.select', ['table' => $tableName]);
 *
 *     // ******* code to be traced goes here *******
 *
 *     TelemetryEventManager::triggerStop();
 *
 * triggerStart() and triggerStop() trigger start/stop events respectively,
 * which set up and tear down a trace segment. The array passed to triggerStart()
 * consists of key/value pairs which set attributes on the trace.
 *
 * Usually, triggerStart() fires an event which in turn causes
 * a child segment to be attached to the root segment. However, if you start a
 * child segment B while another child segment A is already active, the
 * segment B will be added as a child of A rather than root.
 * So, to attach children to the root segment, stop any other child segments
 * before starting a new one.
 *
 * Tracing is automatically stopped and cleaned up by this module via
 * $this->tracer->stopRootSegment() in the onFinish() event handler.
 */
class Module
{
    private Tracer $tracer;

    public function startSegment(Event $e)
    {
        $this->tracer->startSegment($e->getSegmentName(), $e->getAttributes());
    }

    public function stopSegment(Event $e)
    {
        $this->tracer->stopSegment();
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $app = $event->getApplication();

        $this->tracer = $app->getServiceManager()->get('TelemetryTracer');

        // Establish the root segment
        $this->tracer->startRootSegment();

        // Hook up handlers to detect and respond to trace events in the application
        $eventManager = $app->getEventManager();
        $eventManager->attach(Constants::TELEMETRY_START_SEGMENT, [$this, 'startSegment']);
        $eventManager->attach(Constants::TELEMETRY_STOP_SEGMENT, [$this, 'stopSegment']);
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish']);

        // Gives us a globally-accessible Laminas event manager for triggering telemetry events
        TelemetryEventManager::setEventManager($eventManager);
    }

    public function onFinish(MvcEvent $event): void
    {
        $this->tracer->stopRootSegment();
    }
}
