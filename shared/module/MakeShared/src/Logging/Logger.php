<?php

namespace MakeShared\Logging;

use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
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
class Logger extends MonologLogger
{
    /* @var Logger */
    public function __construct(StreamHandler $handler = null)
    {
        parent::__construct();

        $this->pushProcessor(new MvcEventProcessor()); 
        $this->pushProcessor(new HeadersProcessor());
        $this->pushProcessor(new TraceIdProcessor());

        if (is_null($handler)) {
            $handler = new StreamHandler('php://stderr', Level::Warning);
            $handler->setFormatter(new JsonFormatter());  
        }

        $this->pushHandler($handler);
    }

    /**
     * Override the log() method to allow us to append a trace_id field into
     * the $extra argument.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($extra instanceof Traversable) {
            $extra = ArrayUtils::iteratorToArray($context);
        }

        // HACK - get the X-Trace-Id direct from the $_SERVER global
        // if it is set
        if (array_key_exists(Constants::X_TRACE_ID_HEADER_NAME, $_SERVER)) {
            $extra[TraceIdProcessor::TRACE_ID_FIELD_NAME] =
                $_SERVER[Constants::X_TRACE_ID_HEADER_NAME];
        }

        return parent::log($level, $message, $context);
    }
}
