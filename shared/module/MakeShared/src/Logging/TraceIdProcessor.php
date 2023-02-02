<?php

namespace MakeShared\Logging;

use Laminas\Log\Processor\ProcessorInterface;

/**
 * If a non-null trace_id property is in the $extra field for the log event,
 * promote it to a top-level property on the log event and remove it from $extra.
 * If it is present but null, just remove it from $extra.
 */
class TraceIdProcessor implements ProcessorInterface
{
    /**
     * Name of the trace ID field in the $extra array passed to the logger.
     */
    public const TRACE_ID_FIELD_NAME = 'trace_id';

    public function process(array $event): array
    {
        // early return if there's no "trace_id" in $extra
        if (!array_key_exists(self::TRACE_ID_FIELD_NAME, $event['extra'])) {
            return $event;
        }

        $traceId = $event['extra'][self::TRACE_ID_FIELD_NAME];

        if (!is_null($traceId)) {
            $event['trace_id'] = $traceId;
        }

        unset($event['extra'][self::TRACE_ID_FIELD_NAME]);

        return $event;
    }
}
