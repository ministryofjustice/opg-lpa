<?php
namespace Application\Logging;

use Laminas\Mvc\MvcEvent;
use Laminas\Log\Processor\ProcessorInterface;

/**
 * Recognise events sent to the logger and convert them to arrays.
 * This is to enable events to be serialised correctly to JSON:
 * without this processor, they contain circular references which make the
 * JsonFormatter choke.
 *
 * For this to work, the $extra array passed to the log() method must contain
 * an "event" key. The value for this key is converted into an array, removing
 * any circular references.
 */
class EventProcessor implements ProcessorInterface
{
    public const HEADERS_TO_STRIP = ['cookie', 'authorization', '_ga', '_gid'];

    public function process(array $logEvent): array
    {
        // early return if there's no log event in extra
        if (!isset($logEvent['extra']['event']) ||
        !($logEvent['extra']['event'] instanceof MvcEvent)) {
            return $logEvent;
        }

        // pick apart the log event
        $traceId = NULL;
        $laminasEvent = $logEvent['extra']['event'];
        $req = $laminasEvent->getRequest();

        // request headers
        $reqHeadersArray = [];
        $reqHeaders = $req->getHeaders()->toArray();

        foreach ($reqHeaders as $name => $value) {
            $lcaseName = strtolower($name);

            if ($lcaseName === 'x-trace-id') {
                $traceId = $value;
            }

            if (!(in_array($lcaseName, self::HEADERS_TO_STRIP))) {
                $reqHeadersArray[$name] = $value;
            }
        }

        // X-Amzn-Trace-Id, forwarded to the app as X-Trace-Id
        if ($traceId !== NULL) {
            $logEvent['trace_id'] = $traceId;
        }

        // headers and other request data
        $logEvent['request'] = [
            'uri' => $req->getUriString(),
            'method' => $req->getMethod(),
            'headers' => $reqHeadersArray,
        ];

        // event source
        $logEvent['controller'] = $laminasEvent->getController();

        // exception
        $exception = $laminasEvent->getParam('exception');
        if ($exception != NULL) {
            $logEvent['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stackTrace' => $exception->getTraceAsString()
            ];
        }

        // error (if present)
        if ($laminasEvent->isError()) {
            $logEvent['errorMessage'] = $laminasEvent->getError();
        }

        unset($logEvent['extra']);

        return $logEvent;
    }
}
