<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class PhoneNumber extends AbstractData {

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
