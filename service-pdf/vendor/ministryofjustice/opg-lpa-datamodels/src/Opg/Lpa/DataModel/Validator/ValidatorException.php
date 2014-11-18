<?php
namespace Opg\Lpa\DataModel\Validator;

use InvalidArgumentException;

/**
 * Triggered when attempting to set an invalid property value.
 *
 * Class ValidatorException
 * @package Opg\Lpa\DataModel\Validator
 */
class ValidatorException extends InvalidArgumentException {

    protected $validatorResponse = null;

    public function setValidatorResponse( ValidatorResponseInterface $response ){

        $this->validatorResponse = $response;

        return $this;

    } // function

    public function getValidatorResponse(){

        return $this->validatorResponse;

    } // function

} // class
