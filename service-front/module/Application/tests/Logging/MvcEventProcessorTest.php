<?php

namespace ApplicationTest\Logging;

use DateTimeImmutable;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface as Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\Logging\MvcEventProcessor;
use Monolog\Level;
use Monolog\LogRecord;

class MvcEventProcessorTest extends MockeryTestCase
{
    public function testEventToArray()
    {
        $fakeHeadersArray = [
            'Cookie' => 'foo',
            '_ga' => 'GoogleAnalyticsValue',
            '_gid' => 'GoogleAnalyticsId',
            'Authorization' => 'Basic authstring',
            'X-Trace-Id' => 'dddd-sadfs12112-d4231111',
            'Sec-Fetch-Dest' => 'document',
            'Accept' => 'text/html, application/xhtml+xml',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'Upgrade-Insecure-Requests' => 1,
        ];

        $fakeHeaders = Mockery::mock(Headers::class);
        $fakeHeaders->shouldReceive('toArray')->andReturn($fakeHeadersArray)->once();

        $fakeRequest = Mockery::mock(Request::class);
        $fakeRequest->shouldReceive('getHeaders')->andReturn($fakeHeaders)->once();
        $fakeRequest->shouldReceive('getUriString')->andReturn('http://uri')->once();
        $fakeRequest->shouldReceive('getMethod')->andReturn('GET')->once();

        $fakeEvent = Mockery::mock(MvcEvent::class);
        $fakeEvent->shouldReceive('getRequest')->andReturn($fakeRequest)->once();
        $fakeEvent->shouldReceive('getController')->andReturn('MyController')->once();
        $fakeEvent->shouldReceive('getParam')
                  ->with('exception')
                  ->andReturn(null)
                  ->once();
        $fakeEvent->shouldReceive('isError')->andReturn(true)->once();
        $fakeEvent->shouldReceive('getError')->andReturn('generic error')->once();
        $logEvent = new LogRecord(
            datetime: new DateTimeImmutable('2023-07-04T23:59:59+01:00'),
            channel: 'MakeAnLPALogger',
            level: Level::Debug,
            message: 'A log message',
            context: [],
            extra: [
                MvcEventProcessor::EVENT_FIELD_NAME => $fakeEvent
            ],
        );

        $processor = new MvcEventProcessor();
        $actual = $processor($logEvent);

        $expectedLoggedHeaders = [
            'X-Trace-Id' => $fakeHeadersArray['X-Trace-Id'],
            'Sec-Fetch-Dest' => $fakeHeadersArray['Sec-Fetch-Dest'],
            'Accept' => $fakeHeadersArray['Accept'],
            'User-Agent' => $fakeHeadersArray['User-Agent'],
            'Upgrade-Insecure-Requests' => $fakeHeadersArray['Upgrade-Insecure-Requests'],
            '_ga' => 'GoogleAnalyticsValue',
            '_gid' => 'GoogleAnalyticsId',
            'Authorization' => 'Basic authstring',
            'Cookie' => 'foo',
        ];

        $this->assertEquals($expectedLoggedHeaders, $actual['extra']['headers']);

        $this->assertEquals('MyController', $actual['extra']['controller']);
        $this->assertEquals('http://uri', $actual['extra']['request_uri']);
        $this->assertEquals('GET', $actual['extra']['request_method']);
        $this->assertEquals('generic error', $actual['extra']['errorMessage']);
    }
}
