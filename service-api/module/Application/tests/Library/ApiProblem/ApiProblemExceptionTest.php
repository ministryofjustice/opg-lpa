<?php

namespace ApplicationTest\Library\ApiProblem;

use Application\Library\ApiProblem\ApiProblemException;
use PHPUnit\Framework\TestCase;

class ApiProblemExceptionTest extends TestCase
{
    public function testConstructor() : void
    {
        $apiProblemException = new ApiProblemException();

        $this->assertEquals('', $apiProblemException->getMessage());
        $this->assertEquals(500, $apiProblemException->getCode());
        $this->assertEquals(null, $apiProblemException->getPrevious());
    }
}
