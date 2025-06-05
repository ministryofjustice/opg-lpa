<?php

namespace MakeShared\Logging;

use Laminas\Log\Processor\ProcessorInterface;
use MakeShared\Constants;
use Monolog\LogRecord;

/**
 * If a non-null trace_id property is in the $extra field for the log event,
 * promote it to a top-level property on the log event and remove it from $extra.
 * If it is present but null, just remove it from $extra.
 */
class TraceIdProcessor implements \Monolog\Processor\ProcessorInterface
{
    /**
     * Name of the trace ID field in the $extra array passed to the logger.
     */
    public const TRACE_ID_FIELD_NAME = 'trace_id';

    public function __invoke(LogRecord $record): LogRecord
    {
        if (array_key_exists(Constants::X_TRACE_ID_HEADER_NAME, $_SERVER)) {
            $record['extra'][TraceIdProcessor::TRACE_ID_FIELD_NAME] =
                $_SERVER[Constants::X_TRACE_ID_HEADER_NAME];
        }

        return $record;
    }
}
