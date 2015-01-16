<?php
namespace Opg\Lpa\DataModel;

interface ValidatableInterface {

    /**
     * Validates the concrete class which this method is called on.
     *
     * @return \Opg\Lpa\DataModel\Validator\ValidatorResponse
     */
    public function validate();

} // interface
