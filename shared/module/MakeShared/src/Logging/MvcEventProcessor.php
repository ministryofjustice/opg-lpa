<?php

namespace MakeShared\Logging;

use Laminas\Mvc\MvcEvent;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

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
class MvcEventProcessor implements ProcessorInterface
{
    /**
     * Name of the field in the $extra array passed to the logger.
     * If the $extra array contains this key, the value for the key is
     * retrieved and (if an MvcEvent) processed into a JSON-serialisable array.
     */
    public const EVENT_FIELD_NAME = 'event';

    public function __invoke(LogRecord $record): LogRecord
    {
        // early return if there's no "event" in $record
        if (
            !isset($record['extra'][self::EVENT_FIELD_NAME]) ||
            !($record['extra'][self::EVENT_FIELD_NAME] instanceof MvcEvent)
        ) {
            return $record;
        }

        // pick apart the log record
        $laminasEvent = $record['extra'][self::EVENT_FIELD_NAME];
        $req = $laminasEvent->getRequest();

        // raw headers
        $record['extra']['headers'] = $req->getHeaders()->toArray();

        // other request data
        $record['extra']['request_uri'] = $req->getUriString();
        $record['extra']['request_method'] = $req->getMethod();

        // event source controller
        $record['extra']['controller'] = $laminasEvent->getController();

        // exception (if present)
        $exception = $laminasEvent->getParam('exception');
        if ($exception != null) {
            $record['extra']['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stackTrace' => $exception->getTraceAsString()
            ];
        }

        // error (if present)
        if ($laminasEvent->isError()) {
            $record['extra']['errorMessage'] = $laminasEvent->getError();
        }

        // remove the event we've now decomposed
        unset($record['extra'][self::EVENT_FIELD_NAME]);

        return $record;
    }
}
