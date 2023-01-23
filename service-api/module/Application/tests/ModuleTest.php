<?php

namespace ApplicationTest;

use Application\Module;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\Http\Headers;
use Laminas\Http\Header\Accept as AcceptHeader;
use Laminas\Http\Request as LaminasRequest;
use Laminas\Http\Response as LaminasResponse;
use Laminas\Mvc\MvcEvent;
use Mockery;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private function makeEvent(
        string $accept = 'application/json',
        string $contentType = 'application/json'
    ) {
        $requestAcceptHeader = AcceptHeader::fromString("Accept: $accept");

        $request = Mockery::mock(LaminasRequest::class);
        $request->shouldReceive('getHeader')->with('accept')->andReturn($requestAcceptHeader);

        $responseHeaders = Headers::fromString("Content-Type: $contentType");

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

        // check that the response is not an API problem and remains untouched
        $actualResponse = $event->getResponse();
        $this->assertNotEquals(get_class($originalResponse), get_class($actualResponse));
        $this->assertEquals(ApiProblemResponse::class, get_class($actualResponse));
        $this->assertEquals(406, $actualResponse->getStatusCode());
    }
}
