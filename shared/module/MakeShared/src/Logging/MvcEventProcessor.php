<?php

namespace MakeShared\Logging;

use Laminas\Mvc\MvcEvent;
use Laminas\Log\Processor\ProcessorInterface;
use Monolog\LogRecord;

/**
 * Recognise MVC events sent to the logger and convert them to arrays.
 * This is to enable events to be serialised correctly to JSON:
 * without this processor, they contain circular references which make the
 * JsonFormatter choke.
 *
 * For this to work, the $extra array passed to the log() method must contain
 * an "event" key which points to an MvcEvent instance. The value for this
 * key is converted into an array, removing any circular references.
 */
class MvcEventProcessor implements \Monolog\Processor\ProcessorInterface
{
    /**
     * Name of the field in the $extra array passed to the logger.
     * If the $extra array contains this key, the value for the key is
     * retrieved and (if an MvcEvent) processed into a JSON-serialisable array.
     */
    public const EVENT_FIELD_NAME = 'event';

    public function __invoke(LogRecord $event): LogRecord
    {
        // early return if there's no "event" in $extra
        if (
            !isset($event['extra'][self::EVENT_FIELD_NAME]) ||
            !($event['extra'][self::EVENT_FIELD_NAME] instanceof MvcEvent)
        ) {
            return $event;
        }

        // pick apart the log event
        $laminasEvent = $event['extra'][self::EVENT_FIELD_NAME];
        $req = $laminasEvent->getRequest();

        // raw headers
        $event['extra']['headers'] = $req->getHeaders()->toArray();

        // other request data
        $event['extra']['request_uri'] = $req->getUriString();
        $event['extra']['request_method'] = $req->getMethod();

        // event source controller
        $event['extra']['controller'] = $laminasEvent->getController();

        // exception (if present)
        $exception = $laminasEvent->getParam('exception');
        if ($exception != null) {
            $event['extra']['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stackTrace' => $exception->getTraceAsString()
            ];
        }

        // error (if present)
        if ($laminasEvent->isError()) {
            $event['extra']['errorMessage'] = $laminasEvent->getError();
        }

        // remove the event we've now decomposed
        unset($event['extra'][self::EVENT_FIELD_NAME]);

        return $event;
    }
}
