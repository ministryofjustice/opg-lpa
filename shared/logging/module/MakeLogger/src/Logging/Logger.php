<?php

namespace MakeLogger\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\Writer\Stream as StreamWriter;
use Laminas\Log\Formatter\Json as JsonFormatter;
use MakeLogger\Logging\MvcEventProcessor;
use MakeLogger\Logging\HeadersProcessor;
use MakeLogger\Logging\TraceIdProcessor;

/**
 * class Logger
 *
 * A simple StreamWriter file logger which converts log events to JSON.
 */
class Logger extends LaminasLogger
{
    /* @var Logger */
    public function __construct(StreamWriter $writer = null)
    {
        parent::__construct();

        $this->addProcessor(new MvcEventProcessor());
        $this->addProcessor(new HeadersProcessor());
        $this->addProcessor(new TraceIdProcessor());

        if (is_null($writer)) {
            $writer = new StreamWriter('php://stderr');
            $writer->setFormatter(new JsonFormatter());
        }

        $this->addWriter($writer);
    }

    /**
     * Override the log() method to allow us to append a trace_id field into
     * the $extra argument.
     */
    public function log($priority, $message, $extra = [])
    {
        $extra = array($extra);

        // HACK - get the X-Trace-Id direct from the $_SERVER global
        // if it is set
        if (array_key_exists(TraceIdProcessor::X_TRACE_ID_HEADER_NAME, array($_SERVER))) {
            $extra[TraceIdProcessor::TRACE_ID_FIELD_NAME] =
                $_SERVER[TraceIdProcessor::X_TRACE_ID_HEADER_NAME];
        }

        return parent::log($priority, $message, $extra);
    }
}
