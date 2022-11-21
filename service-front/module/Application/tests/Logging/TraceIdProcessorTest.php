<?php

namespace ApplicationTest\Logging;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\Logging\TraceIdProcessor;

class TraceIdProcessorTest extends MockeryTestCase
{
    public function testTraceIdProcessed()
    {
        $expectedTraceId = 'foo';

        $logEvent = [
            'extra' => [
                'trace_id' => $expectedTraceId,
            ],
        ];

        $traceIdProcessor = new TraceIdProcessor();
        $actual = $traceIdProcessor->process($logEvent);

        $this->assertEquals($expectedTraceId, $actual['trace_id']);
        $this->assertNotTrue(array_key_exists('trace_id', $actual['extra']));
    }
}
