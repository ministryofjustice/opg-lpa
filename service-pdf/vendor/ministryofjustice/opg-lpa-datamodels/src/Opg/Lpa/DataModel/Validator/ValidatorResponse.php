<?php
namespace Opg\Lpa\DataModel\Validator;

use ArrayObject;

/**
 * Represents a response from Opg\Lpa\DataModel\Lpa\ValidatableInterface->validate().
 *
 * Class ValidatorResponse
 * @package Opg\Lpa\DataModel\Validator
 */
class ValidatorResponse extends ArrayObject implements ValidatorResponseInterface {

    /**
     * Return true if this response contains one or more errors. False otherwise.
     *
     * @return bool
     */
    public function hasErrors(){
        return ( count( $this ) > 0 );
    }

} // class
