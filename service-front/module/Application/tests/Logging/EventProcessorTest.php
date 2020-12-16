<?php

namespace ApplicationTest\Logging;

use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface as Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Logging\EventProcessor;

class EventProcessorTest extends MockeryTestCase
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
                  ->andReturn(NULL)
                  ->once();
        $fakeEvent->shouldReceive('isError')->andReturn(TRUE)->once();
        $fakeEvent->shouldReceive('getError')->andReturn('generic error')->once();

        $logEvent = [
            'extra' => [
                'event' => $fakeEvent
            ]
        ];

        $processor = new EventProcessor();
        $actual = $processor->process($logEvent);

        $this->assertEquals($fakeHeadersArray['X-Trace-Id'], $actual['trace_id'],
            'X-Trace-Id header should be used to set trace_id value on log event');

        $expectedLoggedHeaders = [
            'X-Trace-Id' => $fakeHeadersArray['X-Trace-Id'],
            'Sec-Fetch-Dest' => $fakeHeadersArray['Sec-Fetch-Dest'],
            'Accept' => $fakeHeadersArray['Accept'],
            'User-Agent' => $fakeHeadersArray['User-Agent'],
            'Upgrade-Insecure-Requests' => $fakeHeadersArray['Upgrade-Insecure-Requests'],
        ];

        $this->assertEquals('MyController', $actual['controller']);
        $this->assertEquals('http://uri', $actual['request']['uri']);
        $this->assertEquals('generic error', $actual['errorMessage']);
        $this->assertEquals($expectedLoggedHeaders, $actual['request']['headers']);
    }
}
