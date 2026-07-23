<?php

namespace ApplicationTest;

use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Module;
use Laminas\Http\Header\Accept as AcceptHeader;
use Laminas\Http\Headers;
use Laminas\Http\Request as LaminasRequest;
use Laminas\Http\Response as LaminasResponse;
use Laminas\Mvc\MvcEvent;
use Mockery;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private function makeEvent(
        string $accept = 'application/json',
        ?string $contentType = null,
    ) {
        $requestAcceptHeader = AcceptHeader::fromString("Accept: $accept");

        $request = Mockery::mock(LaminasRequest::class);
        $request->shouldReceive('getHeader')->with('accept')->andReturn($requestAcceptHeader);

        // when $contentType is null, simulate a response with no
        // Content-Type header at all (rather than one with an empty value)
        $responseHeaders = is_null($contentType)
            ? new Headers()
            : Headers::fromString("Content-Type: $contentType");

        $response = Mockery::mock(LaminasResponse::class);
        $response->shouldReceive('getHeaders')->andReturn($responseHeaders);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);

        return $event;
    }

    public function testNegotiateContentSpecificAccept()
    {
        $event = $this->makeEvent('application/json', 'application/json');

        $originalResponse = $event->getResponse();

        $module = new Module();

        // the existing response should be left alone
        $module->negotiateContent($event);

        // check that the response is not an API problem and remains untouched
        $this->assertEquals(get_class($originalResponse), get_class($event->getResponse()));
    }

    public function testNegotiateContentGenericAccept()
    {
        $event = $this->makeEvent('*/*', 'application/json');

        $originalResponse = $event->getResponse();

        $module = new Module();

        // the existing response should be left alone
        $module->negotiateContent($event);

        // check that the response is not an API problem and remains untouched
        $this->assertEquals(get_class($originalResponse), get_class($event->getResponse()));
    }

    public function testNegotiateContentWillNotAccept()
    {
        $event = $this->makeEvent('application/pdf', 'application/json');

        $originalResponse = $event->getResponse();

        $module = new Module();

        // the existing response should be left alone
        $module->negotiateContent($event);

        // check that the response is replaced with an API problem response
        $actualResponse = $event->getResponse();
        $this->assertNotEquals(get_class($originalResponse), get_class($actualResponse));
        $this->assertEquals(ApiProblemResponse::class, get_class($actualResponse));
        $this->assertEquals(406, $actualResponse->getStatusCode());
    }

    public function testNegotiateNoContentTypeHeaderOnResponse()
    {
        $event = $this->makeEvent('application/pdf', null);

        $originalResponse = $event->getResponse();

        $module = new Module();

        $module->negotiateContent($event);

        // no content-type at all means the response was never actually
        // produced by an API action (e.g. a routing/dispatch failure that
        // already set its own status code, such as a 404) - it should be
        // left alone rather than being masked as a 406
        $this->assertSame($originalResponse, $event->getResponse());
    }

    public function testNegotiateBadContentTypeHeaderOnResponse()
    {
        $event = $this->makeEvent('application/pdf', 'text/html');

        $originalResponse = $event->getResponse();

        $module = new Module();

        $module->negotiateContent($event);

        // a content-type IS present here but doesn't match the Accept
        // header, so this is a genuine negotiation failure and should
        // still be converted to a 406
        $actualResponse = $event->getResponse();
        $this->assertNotEquals(get_class($originalResponse), get_class($actualResponse));
        $this->assertEquals(ApiProblemResponse::class, get_class($actualResponse));
        $this->assertEquals(406, $actualResponse->getStatusCode());
    }
}
