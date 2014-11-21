<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a date of birth.
 *
 * Class Dob
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Dob extends AbstractData {

    /**
     * @var \DateTime A date of birth. The time component of the DateTime object should be ignored.
     */
    protected $date;

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['date'] = function($v){
            return ($v instanceof \DateTime) ? $v : new \DateTime( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['date'] = function(){
            return (new Validator)->addRules([
                new Rules\Instance( 'DateTime' ),
                new Rules\Call(function($input){
                    return ( $input instanceof \DateTime ) ? $input->gettimezone()->getName() : 'UTC';
                }),
                new Rules\Max( new \DateTime('today'), true ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
