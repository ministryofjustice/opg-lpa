<?php

namespace OpgTest\Lpa\Logger;

use DateTime;
use Opg\Lpa\Logger\Formatter\Logstash;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use Zend\Log\Formatter\FormatterInterface;

class LogstashTest extends TestCase
{
    /**
     * @var Logstash
     */
    private $formatter;

    public function setUp()
    {
        $this->formatter = new Logstash();
    }

    public function testSetEncodingFromConstructor()
    {
        $encoding = 'ASCII';
        $formatter = new Logstash(['encoding' => $encoding]);

        $this->assertEquals($encoding, $formatter->getEncoding());
    }

    public function testSetDateTimeFormat()
    {
        $this->assertEquals(FormatterInterface::DEFAULT_DATETIME_FORMAT, $this->formatter->getDateTimeFormat());

        $this->formatter->setDateTimeFormat(DateTime::ISO8601);

        $this->assertEquals(DateTime::ISO8601, $this->formatter->getDateTimeFormat());
    }

    public function testFormatEventString()
    {
        $now = new DateTime();
        $event = [
            'message' => 'Alert',
            'timestamp' => $now
        ];

        $formatted = $this->formatter->format($event);

        $expected = [
            '@version' => 1,
            '@timestamp' => $now->format(FormatterInterface::DEFAULT_DATETIME_FORMAT),
            'host' => 'Unknown',
            'uri' => 'Unknown',
            'message' => 'Alert',
            'timestamp' => $now->format(FormatterInterface::DEFAULT_DATETIME_FORMAT)
        ];

        $this->assertEquals(json_encode($expected), $formatted);
    }

    public function testFormatEventStringExtra()
    {
        $now = new DateTime();
        $event = [
            'message' => 'Alert',
            'extra' => 'Extra',
            'timestamp' => $now
        ];

        $formatted = $this->formatter->format($event);

        $expected = [
            '@version' => 1,
            '@timestamp' => $now->format(FormatterInterface::DEFAULT_DATETIME_FORMAT),
            'host' => 'Unknown',
            'uri' => 'Unknown',
            'message' => 'Alert',
            'extra' => 'Extra',
            'timestamp' => $now->format(FormatterInterface::DEFAULT_DATETIME_FORMAT)
        ];

        $this->assertEquals(json_encode($expected), $formatted);
    }

    public function testFormatEventStringExtraArray()
    {
        $now = new DateTime();
        $event = [
            'message' => 'Alert',
            'extra' => ['Extra'],
            'timestamp' => $now
        ];

        $formatted = $this->formatter->format($event);

        $expected = [
            '@version' => 1,
            '@timestamp' => $now->format(FormatterInterface::DEFAULT_DATETIME_FORMAT),
            'host' => 'Unknown',
            'uri' => 'Unknown',
            'message' => 'Alert',
            'timestamp' => $now->format(FormatterInterface::DEFAULT_DATETIME_FORMAT),
            'extra' => ['Extra']
        ];

        $this->assertEquals(json_encode($expected), $formatted);
    }
}