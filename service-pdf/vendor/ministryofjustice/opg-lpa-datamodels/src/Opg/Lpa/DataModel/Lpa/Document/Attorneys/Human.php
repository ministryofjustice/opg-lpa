<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Human extends AbstractAttorney {

    protected $name;
    protected $dob;

    public function __construct( $data ){

        //-----------------------------------------------------
        // Type mappers

        $this->typeMap['name'] = function($v){
            return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
        };

        $this->typeMap['dob'] = function($v){
            return ($v instanceof Elements\Dob) ? $v : new Elements\Dob( $v );
        };

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        //---

        parent::__construct( $data );

    } // function

} // class
