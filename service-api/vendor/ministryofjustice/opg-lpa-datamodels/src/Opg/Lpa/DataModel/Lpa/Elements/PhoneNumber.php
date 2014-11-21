<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a phone number.
 *
 * Class PhoneNumber
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class PhoneNumber extends AbstractData {

    /**
     * @var string A phone number.
     */
    protected $number;

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['number'] = function(){
            return (new Validator)->addRules([
                new Rules\Phone,
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
