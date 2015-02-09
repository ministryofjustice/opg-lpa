<?php

use Opg\Lpa\DataModel\Validator\ValidatorResponse;
use Opg\Lpa\DataModel\Validator\ValidatorException;

class ValidatorExceptionTest extends PHPUnit_Framework_TestCase {

    /**
     * Tests the setter and getter on ValidatorException.
     */
    public function testSettingValidatorResponse(){

        $v = new ValidatorResponse();

        $v['error'] = true;

        //---

        $e = new ValidatorException();

        $e->setValidatorResponse( $v );

        //---

        $this->assertEquals( $v ,  $e->getValidatorResponse(), 'Returned validator did not match one set.' );

    }

    /**
     * Test ValidatorException is throwable; and indirectly that it extends InvalidArgumentException.
     *
     * @expectedException InvalidArgumentException
     */
    public function testValidatorExceptionIsThrowable(){

        throw new ValidatorException( 'A test exception.' );

    }

} // class
