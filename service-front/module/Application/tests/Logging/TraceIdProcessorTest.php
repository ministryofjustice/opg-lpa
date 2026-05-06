<?php

declare(strict_types=1);

namespace ApplicationTest\Logging;

use DateTimeImmutable;
use MakeShared\Constants;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\Logging\TraceIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;

final class TraceIdProcessorTest extends MockeryTestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER[Constants::X_TRACE_ID_HEADER_NAME], $_SERVER['HTTP_X_REQUEST_ID']);
        parent::tearDown();
    }

    public function testTraceIdProcessed(): void
    {
        $_SERVER[Constants::X_TRACE_ID_HEADER_NAME] = 'trace-from-header';

        $logEvent = new LogRecord(
            datetime: new DateTimeImmutable('2023-07-04T23:59:59+01:00'),
            channel: 'MakeAnLPALogger',
            level: Level::Debug,
            message: 'A log message',
            context: [],
            extra: [],
        );

        $traceIdProcessor = new TraceIdProcessor();
        $actual = $traceIdProcessor($logEvent);

        $this->assertEquals('trace-from-header', $actual['extra']['trace_id']);
    }

    public function testTraceIdGeneratedWhenNoHeaderPresent(): void
    {
        $logEvent = new LogRecord(
            datetime: new DateTimeImmutable('2023-07-04T23:59:59+01:00'),
            channel: 'MakeAnLPALogger',
            level: Level::Debug,
            message: 'A log message',
            context: [],
            extra: [],
        );

        $traceIdProcessor = new TraceIdProcessor();
        $actual = $traceIdProcessor($logEvent);

        $this->assertIsString($actual['extra']['trace_id']);
        $this->assertNotSame('', $actual['extra']['trace_id']);
    }

    public function testExistingTraceIdWinsOverHeaders(): void
    {
        $_SERVER[Constants::X_TRACE_ID_HEADER_NAME] = 'trace-from-header';

        $logEvent = new LogRecord(
            datetime: new DateTimeImmutable('2023-07-04T23:59:59+01:00'),
            channel: 'MakeAnLPALogger',
            level: Level::Debug,
            message: 'A log message',
            context: [],
            extra: [
                'trace_id' => 'trace-from-extra',
            ],
        );

        $traceIdProcessor = new TraceIdProcessor();
        $actual = $traceIdProcessor($logEvent);

        $this->assertEquals('trace-from-extra', $actual['extra']['trace_id']);
    }
}
