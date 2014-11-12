<?php
namespace Opg\Lpa\DataModel\Lpa;

interface ValidatableInterface {

    /**
     * Validate the LPA.
     *
     * if $property is:
     *  - null: Validate all properties.
     *  - Array: Validate all properties listed in the array.
     *  - string: Validate the single named property.
     *
     * In all cases if a property's value implements Lpa\ValidatableInterface,
     * the validation request should be propagated.
     *
     * @param string|Array|null $property
     * @return \Opg\Lpa\DataModel\Validator\Errors
     */
    public function validate($property);

} // interface
