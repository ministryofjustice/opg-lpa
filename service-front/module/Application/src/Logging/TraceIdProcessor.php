<?php
namespace Application\Logging;

use Laminas\Log\Processor\ProcessorInterface;

/**
 * If a non-null trace_id property is in the $extra field for the log event,
 * promote it to a top-level property on the log event and remove it from $extra.
 * If it is present but null, just remove it from $extra.
 */
class TraceIdProcessor implements ProcessorInterface
{
    /**
     * Name of the incoming trace ID header on the $_SERVER object.
     */
    public const X_TRACE_ID_HEADER_NAME = 'HTTP_X_TRACE_ID';

    /**
     * Name of the trace ID field in the $extra array passed to the logger.
     */
    public const TRACE_ID_FIELD_NAME = 'trace_id';

    public function process(array $logEvent): array
    {
        // early return if there's no "trace_id" in $extra
        if (!array_key_exists(self::TRACE_ID_FIELD_NAME, $logEvent['extra'])) {
            return $logEvent;
        }

        $traceId = $logEvent['extra'][self::TRACE_ID_FIELD_NAME];

        if (!is_null($traceId)) {
            $logEvent['trace_id'] = $traceId;
        }

        unset($logEvent['extra'][self::TRACE_ID_FIELD_NAME]);

        return $logEvent;
    }
}
