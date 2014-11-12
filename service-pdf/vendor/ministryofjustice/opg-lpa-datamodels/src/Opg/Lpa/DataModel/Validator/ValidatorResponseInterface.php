<?php
namespace Opg\Lpa\DataModel\Validator;

use IteratorAggregate, ArrayAccess, Serializable, Countable;

interface ValidatorResponseInterface extends IteratorAggregate, ArrayAccess, Serializable, Countable {

    public function hasErrors();

} // interface
