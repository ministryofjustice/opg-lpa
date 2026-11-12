<?php

namespace MakeSharedTest\Telemetry;

use DateTimeImmutable;
use MakeShared\Logging\MetricsLogger;
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
        $this->assertEquals('missing-trace-id', $formattedRecord['trace_id']);

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

    public function testFormatEmitsEmfFieldsForMetricEvents()
    {
        $formatter = new OpgJsonFormatter();

        $record = new LogRecord(
            new DateTimeImmutable('2025-04-16T16:25:17Z'),
            'my-service',
            Level::Info,
            'User joined shared space',
            [
                'event' => MetricsLogger::SHARED_SPACE_MEMBER_JOINED,
                MetricsLogger::CONTEXT_DIMENSIONS_KEY => ['IsAdmin' => 'true'],
                'shared_space_id' => 'abc',
            ],
        );

        $formattedRecord = json_decode($formatter->format($record), JSON_THROW_ON_ERROR);

        $this->assertEquals('User joined shared space', $formattedRecord['msg']);
        $this->assertEquals('abc', $formattedRecord['context']['shared_space_id']);
        $this->assertArrayNotHasKey(MetricsLogger::CONTEXT_DIMENSIONS_KEY, $formattedRecord['context']);

        $this->assertEquals(1, $formattedRecord['EventCount']);
        $this->assertEquals('event.shared_space.member.joined', $formattedRecord['Event']);
        $this->assertEquals('true', $formattedRecord['IsAdmin']);

        $this->assertSame(1744820717000, $formattedRecord['_aws']['Timestamp']);

        $metric = $formattedRecord['_aws']['CloudWatchMetrics'][0];
        $this->assertEquals('opg-lpa', $metric['Namespace']);
        $this->assertEquals([['Event'], ['Event', 'IsAdmin']], $metric['Dimensions']);
        $this->assertEquals([['Name' => 'EventCount', 'Unit' => 'Count']], $metric['Metrics']);
    }

    public function testFormatDoesNotEmitEmfFieldsForUnknownEvents()
    {
        $formatter = new OpgJsonFormatter();

        $record = new LogRecord(
            new DateTimeImmutable('2025-04-16T16:25:17Z'),
            'my-service',
            Level::Info,
            'message contents',
            ['event' => 'not.a.metric'],
        );

        $formattedRecord = json_decode($formatter->format($record), JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('_aws', $formattedRecord);
        $this->assertArrayNotHasKey('EventCount', $formattedRecord);
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
