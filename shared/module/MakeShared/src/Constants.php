<?php

namespace MakeShared;

class Constants
{
     /**
      * Name of the incoming trace ID header on the $_SERVER object.
      */
    const X_TRACE_ID_HEADER_NAME = 'HTTP_X_TRACE_ID';

    /*
     * Telemetry event identifiers
     */
    const TELEMETRY_START_SEGMENT = 'telemetry-start-segment';
    const TELEMETRY_STOP_SEGMENT = 'telemetry-stop-segment';

    /*
     * Status codes for /ping endpoints
     */
    const STATUS_UNKNOWN = 'unknown';
    const STATUS_PASS = 'pass';
    const STATUS_FAIL = 'fail';
    const STATUS_WARN = 'warn';
}
