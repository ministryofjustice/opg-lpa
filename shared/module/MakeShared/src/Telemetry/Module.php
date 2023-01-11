<?php

declare(strict_types=1);

namespace Telemetry;

use Laminas\Mvc\MvcEvent;
use MakeShared\Constants;
use MakeShared\Telemetry\Event;
use MakeShared\Telemetry\TelemetryEventManager;
use MakeShared\Telemetry\Tracer;

/**
 * Set up event handlers for capturing trace events triggered in the code.
 * This approach was used rather than singletons so that the tracer
 * instance can be configured like any other Laminas service. To start a new
 * span in code, use the TelemetryEventManager to trigger start/stop child
 * events.
 *
 * To incorporate telemetry into your Laminas application, you'll need to load
 * this module in application.config.php:
 *
 * return array(
 *     'modules' => array(
 *         // ... other modules here ...
 *
 *         'Telemetry',
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
 * You will also need to add a telemetry tracer resource to your application module,
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
 * Note that here we pass in some config which contains an exporter.url property
 * which points to an aws-otel-collector in prod and is empty in dev (resulting
 * in all telemetry being written to the console).
 * This is mainly used to configure the exporter used by the Tracer. If you need
 * more flexibility, it would be fairly easy to refactor the Tracer class into
 * an interface, then implement a tracer with different OpenTelemetry configuration.
 *
 * The $this->tracer->start() call in this module sets up the root span.
 *
 * Then, to trace an individual piece of code, surround it like this:
 *
 *     use MakeShared\Telemetry\TelemetryEventManager;
 *
 *     TelemetryEventManager::triggerStart('DbWrapper.select', ['table' => $tableName]);
 *
 *     // ******* code to be traced goes here *******
 *
 *     TelemetryEventManager::triggerStop('DbWrapper.select');
 *
 * triggerStart() and triggerStop() trigger start/stop events respectively,
 * which set up and tear down a trace span. The array passed to triggerStart()
 * consists of key/value pairs which set attributes on the trace.
 *
 * Usually, triggerStart() fires an event which in turn causes
 * a child span to be attached to the root span. However, if you start a
 * child span B while another child span A is already active, the
 * OT API will make B a child of A rather than root.
 * So, to attach children to the root span, stop any other child spans before starting
 * a new one.
 *
 * Tracing is automatically stopped and cleaned up by this module via
 * $this->tracer->stop().
 */
class Module
{
    private Tracer $tracer;

    public function startChild(Event $e)
    {
        $this->tracer->startChild($e->getSegmentName(), $e->getAttributes());
    }

    // when stopping a child, we ignore the event's attributes
    public function stopChild(Event $e)
    {
        $this->tracer->stopChild($e->getSegmentName());
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $app = $event->getApplication();

        $this->tracer = $app->getServiceManager()->get('TelemetryTracer');

        // Establish the root trace
        $this->tracer->start();

        $eventManager = $app->getEventManager();
        $eventManager->attach(Constants::TELEMETRY_START_CHILD, [$this, 'startChild']);
        $eventManager->attach(Constants::TELEMETRY_STOP_CHILD, [$this, 'stopChild']);
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish']);

        // Gives us a globally-accessible Laminas event manager for triggering telemetry events
        TelemetryEventManager::setEventManager($eventManager);
    }

    public function onFinish(MvcEvent $event): void
    {
        $this->tracer->stop();
    }
}
