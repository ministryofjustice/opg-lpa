<?php
namespace Opg\Lpa\DataModel\Validator;

use InvalidArgumentException;

class ValidatorException extends InvalidArgumentException {

    protected $validatorResponse;

    public function setValidatorResponse( ValidatorResponseInterface $response ){

        $this->validatorResponse = $response;

        return $this;

    } // function

} // class
