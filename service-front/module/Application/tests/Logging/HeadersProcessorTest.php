<?php

namespace ApplicationTest\Logging;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MakeShared\Logging\HeadersProcessor;

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

        $logEvent = [
            'extra' => [
                'headers' => $fakeHeadersArray,
            ],
        ];

        $headersProcessor = new HeadersProcessor();
        $actual = $headersProcessor->process($logEvent);

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
