<?php

namespace MakeShared;

class Constants
{
     /**
      * Name of the incoming trace ID header on the $_SERVER object.
      */
    public const string X_TRACE_ID_HEADER_NAME = 'HTTP_X_TRACE_ID';

    /*
     * Name of the trace ID field in the $extra array passed to the logger.
     */
    public const string TRACE_ID_FIELD_NAME = 'trace_id';
    /*
     * Telemetry event identifiers
     */
    public const string TELEMETRY_START_SEGMENT = 'telemetry-start-segment';
    public const string TELEMETRY_STOP_SEGMENT = 'telemetry-stop-segment';

    /*
     * Status codes for /ping endpoints
     */
    public const string STATUS_UNKNOWN = 'unknown';
    public const string STATUS_PASS = 'pass';
    public const string STATUS_FAIL = 'fail';
    public const string STATUS_WARN = 'warn';
}
