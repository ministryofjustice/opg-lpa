<?php

namespace ApplicationTest\Logging;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\Logging\HeadersProcessor;
use Monolog\Level;
use Monolog\LogRecord;

class HeadersProcessorTest extends MockeryTestCase
{
    public function testHeadersProcessed()
    {
        $fakeHeadersArray = [
            'Cookie' => 'foo',
            '_ga' => 'GoogleAnalyticsValue',
            '_gid' => 'GoogleAnalyticsId',
            'Authorization' => 'Basic authstring',
            'X-Trace-Id' => '999-000-111-222',
            'Sec-Fetch-Dest' => 'document',
            'Accept' => 'text/html, application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'Upgrade-Insecure-Requests' => 1,
            'Token' => 'ffasdfasdfasasdasd',
        ];

        $logEvent = new LogRecord(
            datetime: new DateTimeImmutable('2023-07-04T23:59:59+01:00'),
            channel: 'MakeAnLPALogger',
            level: Level::Debug,
            message: 'A log message',
            context: [],
            extra: [
                'headers' => $fakeHeadersArray,
            ],
        );

        $headersProcessor = new HeadersProcessor();
        $actual = $headersProcessor($logEvent);

        $expectedHeaders = [
            'X-Trace-Id' => '999-000-111-222',
            'Sec-Fetch-Dest' => 'document',
            'Accept' => 'text/html, application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'Upgrade-Insecure-Requests' => 1,
        ];

        $this->assertEquals($expectedHeaders, $actual['extra']['headers']);
    }
}
