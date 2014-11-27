<?php
namespace Opg\Lpa\DataModel\Validator;

use IteratorAggregate, ArrayAccess, Serializable, Countable;

interface ValidatorResponseInterface extends IteratorAggregate, ArrayAccess, Serializable, Countable {

    /**
     * Return true iff this response contains one or more errors. False otherwise.
     *
     * @return bool
     */
    public function hasErrors();

} // interface
