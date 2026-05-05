<?php

namespace MakeShared\Logging;

use MakeShared\Constants;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * If a non-null trace_id property is in the $extra field for the log event,
 * promote it to a top-level property on the log event and remove it from $extra.
 * If it is present but null, just remove it from $extra.
 */
class TraceIdProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $traceId = $record->extra[Constants::TRACE_ID_FIELD_NAME]
            ?? $record->context[Constants::TRACE_ID_FIELD_NAME]
            ?? $_SERVER[Constants::X_TRACE_ID_HEADER_NAME]
            ?? $_SERVER['HTTP_X_REQUEST_ID']
            ?? null;

        if (!is_string($traceId) || $traceId === '') {
            try {
                $traceId = bin2hex(random_bytes(16));
            } catch (\Throwable) {
                $traceId = uniqid('trace-', true);
            }
        }

        $record->extra[Constants::TRACE_ID_FIELD_NAME] = $traceId;

        return $record;
    }
}
