<?php

namespace ApplicationTest\Logging;

use Laminas\Log\Writer\Stream as StreamWriter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use MakeLogger\Logging\Logger;

class LoggerTest extends MockeryTestCase
{
    public function testLoggerWithTraceIdHeaderInSERVER()
    {
        $traceId = 'in_unit_test_environment';
        $msg = 'hello world';

        // mock incoming X-Trace-Id header
        $_SERVER['HTTP_X_TRACE_ID'] = $traceId;

        $mockWriter = Mockery::mock(StreamWriter::class);
        $mockWriter->shouldReceive('write')
                   ->withArgs(function ($args) use ($traceId, $msg) {
                       return $args['trace_id'] === $traceId &&
                           $args['message'] === $msg;
                   })
                   ->once();

        $logger = new Logger($mockWriter);
        $logger->info($msg);
    }
}
