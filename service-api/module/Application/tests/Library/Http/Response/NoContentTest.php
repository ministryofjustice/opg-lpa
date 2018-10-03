<?php

namespace Library\Http\Response;

use Application\Library\Http\Response\NoContent;
use PHPUnit\Framework\TestCase;

class NoContentTest extends TestCase
{
    public function testConstructor()
    {
        $result = new NoContent();
        $this->assertEquals(204, $result->getStatusCode());
    }
}
