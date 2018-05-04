<?php

namespace ApplicationTest\Model\Http\PhpEnvironment;

use Application\Model\Http\PhpEnvironment\JsonRequest;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class JsonRequestTest extends MockeryTestCase
{
    public function testConstruct()
    {
        $request = new TestableJsonRequest(null, null);

        $this->assertInstanceOf(JsonRequest::class, $request);
    }

    public function testConstructHeader()
    {
        $request = new TestableJsonRequest('unit/test', null);

        $this->assertInstanceOf(JsonRequest::class, $request);
    }

    public function testConstructHeaderJson()
    {
        $request = new TestableJsonRequest('application/json', '{}');

        $this->assertInstanceOf(JsonRequest::class, $request);
    }
}