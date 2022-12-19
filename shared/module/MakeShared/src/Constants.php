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
    const TELEMETRY_START_CHILD = 'telemetry-start-child';
    const TELEMETRY_STOP_CHILD = 'telemetry-stop-child';
}
