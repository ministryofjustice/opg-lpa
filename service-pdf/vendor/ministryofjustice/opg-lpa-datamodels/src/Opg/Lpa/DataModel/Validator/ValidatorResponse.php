<?php
namespace Opg\Lpa\DataModel\Validator;

use ArrayObject;

class ValidatorResponse extends ArrayObject implements ValidatorResponseInterface {

    public function hasErrors(){
        return ( count( $this ) > 0 );
    }

} // class
