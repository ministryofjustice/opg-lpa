<?php

namespace ApplicationTest\Logging;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\Logging\TraceIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;

class TraceIdProcessorTest extends MockeryTestCase
{
    public function testTraceIdProcessed()
    {
        $expectedTraceId = 'foo';

        $logEvent = new LogRecord(
            datetime: new DateTimeImmutable('2023-07-04T23:59:59+01:00'),
            channel: 'MakeAnLPALogger',
            level: Level::Debug,
            message: 'A log message',
            context: [],
            extra: [
                'trace_id' => $expectedTraceId,
            ],
        );

        $traceIdProcessor = new TraceIdProcessor();
        $actual = $traceIdProcessor($logEvent);

        $this->assertEquals($expectedTraceId, $actual['extra']['trace_id']);
    }
}
