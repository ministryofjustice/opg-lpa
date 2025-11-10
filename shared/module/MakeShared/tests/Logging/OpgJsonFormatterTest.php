<?php

namespace MakeSharedTest\Telemetry;

use DateTimeImmutable;
use MakeShared\Logging\OpgJsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class OpgJsonFormatterTest extends TestCase
{
    public function testFormatRestructuresMinimalRecord()
    {
        $formatter = new OpgJsonFormatter();

        $record = new LogRecord(
            new DateTimeImmutable('2025-04-16T16:25:17Z'),
            'my-service',
            Level::Error,
            'message contents',
        );

        $formattedRecord = json_decode($formatter->format($record), JSON_THROW_ON_ERROR);

        $this->assertEquals('2025-04-16T16:25:17+00:00', $formattedRecord['time']);
        $this->assertEquals('ERROR', $formattedRecord['level']);
        $this->assertEquals('message contents', $formattedRecord['msg']);
        $this->assertEquals('my-service', $formattedRecord['service_name']);

        $this->assertArrayNotHasKey('datetime', $formattedRecord);
        $this->assertArrayNotHasKey('level_name', $formattedRecord);
        $this->assertArrayNotHasKey('message', $formattedRecord);
        $this->assertArrayNotHasKey('channel', $formattedRecord);
    }

    public function testFormatRestructuresOptionalFields()
    {
        $formatter = new OpgJsonFormatter();

        $record = new LogRecord(
            new DateTimeImmutable('2025-04-16T16:25:17Z'),
            'my-service',
            Level::Error,
            'message contents',
            ['additional_context' => 'value'],
            [
                'trace_id' => 'THETRACEID',
                'other_extra' => 'extra',
            ],
        );

        $formattedRecord = json_decode($formatter->format($record), JSON_THROW_ON_ERROR);

        $this->assertEquals('value', $formattedRecord['context']['additional_context']);
        $this->assertEquals('extra', $formattedRecord['extra']['other_extra']);
        $this->assertEquals('THETRACEID', $formattedRecord['trace_id']);

        $this->assertArrayNotHasKey('trace_id', $formattedRecord['extra']);
    }

    public function testFormatAttachesRequestInfo()
    {
        $formatter = new OpgJsonFormatter();

        $formatter->requestMethod = 'GET';
        $formatter->requestPath = '/v1/dashboard';

        $record = new LogRecord(
            new DateTimeImmutable('2025-04-16T16:25:17Z'),
            'my-service',
            Level::Error,
            'message contents',
        );

        $formattedRecord = json_decode($formatter->format($record), JSON_THROW_ON_ERROR);

        $this->assertEquals('GET', $formattedRecord['request']['method']);
        $this->assertEquals('/v1/dashboard', $formattedRecord['request']['path']);
    }
}
