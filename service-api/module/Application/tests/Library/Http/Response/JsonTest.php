<?php

namespace ApplicationTest\Library\Http\Response;

use Application\Library\Http\Response\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testConstructor()
    {
        $result = new Json(['key' => 'value'], 100);
        $this->assertEquals(100, $result->getStatusCode());

        $headers = $result->getHeaders();

        $this->assertEquals(['content-type' => 'application/json'], $headers->toArray());
    }

    public function testGetContent()
    {
        $result = new Json(['key' => 'value', 'key2' => 'value2'], 100);

        $this->assertEquals('{"key":"value","key2":"value2"}', $result->getContent());
    }
}
