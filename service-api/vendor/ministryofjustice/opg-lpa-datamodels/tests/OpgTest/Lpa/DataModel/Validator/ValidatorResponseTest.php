<?php

namespace OpgTest\Lpa\DataModel\Validator;

use Opg\Lpa\DataModel\Validator\ValidatorResponse;
use PHPUnit\Framework\TestCase;

class ValidatorResponseTest extends TestCase
{

    /**
     * Test that with no errors, hasErrors() returns false.
     */
    public function testNoErrors()
    {
        $v = new ValidatorResponse();

        $this->assertFalse($v->hasErrors(), 'The response should contain no errors.');
    }

    /**
     * Test that with an error, hasErrors() returns true.
     */
    public function testWithErrors()
    {
        $v = new ValidatorResponse();

        $this->assertFalse($v->hasErrors(), 'The response should contain no errors.');

        $v['error'] = 'There was an error.';

        $this->assertTrue($v->hasErrors(), 'The response should contain 1 error.');
    }
}
