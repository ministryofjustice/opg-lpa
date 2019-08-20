<?php

namespace ApplicationTest\Library\Authorization;

use Application\Library\Authorization\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class UnauthorizedExceptionTest extends TestCase
{
    public function testConstructor() : void
    {
        $unauthorizedException = new UnauthorizedException();

        $this->assertEquals('', $unauthorizedException->getMessage());
        $this->assertEquals(401, $unauthorizedException->getCode());
        $this->assertEquals(null, $unauthorizedException->getPrevious());
    }
}
