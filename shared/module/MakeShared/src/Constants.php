<?php

namespace MakeShared;

class Constants
{
     /**
      * Name of the incoming trace ID header on the $_SERVER object.
      */
    public const X_TRACE_ID_HEADER_NAME = 'HTTP_X_TRACE_ID';

    /*
     * Telemetry event identifiers
     */
    public const TELEMETRY_START_SEGMENT = 'telemetry-start-segment';
    public const TELEMETRY_STOP_SEGMENT = 'telemetry-stop-segment';

    /*
     * Status codes for /ping endpoints
     */
    public const STATUS_UNKNOWN = 'unknown';
    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_WARN = 'warn';
}
