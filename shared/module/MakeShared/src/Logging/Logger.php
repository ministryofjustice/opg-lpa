<?php

namespace MakeShared\Logging;

use Laminas\Log\Logger as LaminasLogger;
use Laminas\Log\Writer\Stream as StreamWriter;
use Laminas\Log\Formatter\Json as JsonFormatter;
use Laminas\Stdlib\ArrayUtils;
use MakeShared\Constants;
use MakeShared\Logging\MvcEventProcessor;
use MakeShared\Logging\HeadersProcessor;
use MakeShared\Logging\TraceIdProcessor;
use Traversable;

/**
 * class Logger
 *
 * A simple StreamWriter file logger which converts log events to JSON.
 */
class Logger extends LaminasLogger  // TODO this should no longer be used but may need to add processors like below, to new way of logging,
    probably needs to be in getLogger to add the tracing
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
        if ($extra instanceof Traversable) {
            $extra = ArrayUtils::iteratorToArray($extra);
        }

        // HACK - get the X-Trace-Id direct from the $_SERVER global
        // if it is set
        if (array_key_exists(Constants::X_TRACE_ID_HEADER_NAME, $_SERVER)) {
            $extra[TraceIdProcessor::TRACE_ID_FIELD_NAME] =
                $_SERVER[Constants::X_TRACE_ID_HEADER_NAME];
        }

        return parent::log($priority, $message, $extra);
    }
}
