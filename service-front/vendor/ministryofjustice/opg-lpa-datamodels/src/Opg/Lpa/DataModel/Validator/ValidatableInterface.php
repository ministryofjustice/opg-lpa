<?php
namespace Opg\Lpa\DataModel\Validator;

interface ValidatableInterface {

    /**
     * Validates the concrete class which this method is called on.
     *
     * @param $properties Array An array of property names to check. An empty array means all properties.
     * @return \Opg\Lpa\DataModel\Validator\ValidatorResponse
     */
    public function validate( Array $properties = array() );

} // interface
