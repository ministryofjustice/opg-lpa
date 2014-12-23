<?php

use Respect\Validation\Rules;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Validator;

//---------------------------------------------------------------------
// Setup test classes

/**
 * Dummy class allowing access to a concrete instance of AbstractData.
 */
class DummyAbstractDataTestOne extends AbstractData {
    protected $dummyValue;
}

/**
 * Dummy class for the testing of validators.
 */
class DummyAbstractDataTestWithValidator extends DummyAbstractDataTestOne {

    public function __construct(){

        $this->validators['dummyValue'] = function(){
            return (new Validator)->addRules([
                new Rules\Int,
                new Rules\Between( 0, 99999999999, true ),
            ]);
        };

    }

} // class

/**
 * Dummy class for the testing of mappers.
 */
class DummyAbstractDataTestWithMapper extends DummyAbstractDataTestOne {

    public function __construct(){

        // The test will map a string passed to dummyValue to a DateTime object.
        $this->typeMap['dummyValue'] = function($v){
            return ($v instanceof DateTime) ? $v : new DateTime( $v );
        };

    }

} // class

//---------------------------------------------------------------------

class AbstractDataTest extends PHPUnit_Framework_TestCase {

    protected $data;
    protected $dataWithValidator;
    protected $dataWithMapper;

    protected function setUp(){

        $this->data = new DummyAbstractDataTestOne();
        $this->dataWithValidator = new DummyAbstractDataTestWithValidator();
        $this->dataWithMapper = new DummyAbstractDataTestWithMapper();

    }

    //----------------------------

    /**
     * Test the getting and setting of a existing property.
     */
    public function testExceptionForGettingAndSettingValidProperty(){

        $value = $this->data->dummyValue;

        $this->assertNull( $value, 'Value should initially be null.' );

        $this->data->dummyValue = true;

        $value = $this->data->dummyValue;

        $this->assertTrue( $value, 'Value should have been set to true.' );

    } // function

    //----------------------------

    /**
     * Test we get an exception when trying to get an non-existent property.
     *
     * @expectedException InvalidArgumentException
     */
    public function testExceptionForGettingNonexistentProperty(){

        $test = $this->data->test;

    } // function

    /**
     * Test we get an exception when trying to set an non-existent property.
     *
     * @expectedException InvalidArgumentException
     */
    public function testExceptionForSettingNonexistentProperty(){

        $this->data->test = 'Test';

    } // function

    //----------------------------
    // Basic Validation tests

    public function testSettingValidPropertyThenValidating(){

        $this->dataWithValidator->set( 'dummyValue', 123, false );

        $response = $this->dataWithValidator->validate();

        $this->assertFalse( $response->hasErrors(), 'There should be no errors returned.' );

    } // function

    public function testSettingInvalidPropertyThenValidating(){

        // This is invalid, but shouldn't be picked up as we pass $validate=false
        $this->dataWithValidator->set( 'dummyValue', 'Int expected here', false );

        $response = $this->dataWithValidator->validate();

        $this->assertTrue( $response->hasErrors(), 'There should be an error returned.' );

    } // function

    //----------------------------
    // Basic Mapping tests

    public function testDataIsMapped(){

        $this->dataWithMapper->dummyValue = '2014-11-17';

        $this->assertInstanceOf( 'DateTime', $this->dataWithMapper->dummyValue, 'Value should have been mapped to a DateTime.' );

    } // function

    //----------------------------
    // Test the constructor

    public function testConstructPopulator(){

        $data = new DummyAbstractDataTestOne( '{"dummyValue": "ad353da6b73ceee2201cee2f9936c509"}' );

        //---------------------------------------------------
        // Check the export to JSON ( and thus toArray() )

        $out = json_decode( $data->toJson() );

        $this->assertEquals( 'ad353da6b73ceee2201cee2f9936c509', $out->dummyValue, 'dummyValue should match what was initially set.' );

        //---------------------------------------------------
        // Check flat array export...

        $out = $data->flatten();

        $this->assertEquals( 'ad353da6b73ceee2201cee2f9936c509', $out['lpa-dummyValue'], 'dummyValue should match what was initially set.' );

    } // function

} // class
