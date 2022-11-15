<?php

namespace MakeShared\DataModel\Validator;

use IteratorAggregate;
use ArrayAccess;
use Serializable;
use Countable;

interface ValidatorResponseInterface extends IteratorAggregate, ArrayAccess, Serializable, Countable
{
    /**
     * Return true iff this response contains one or more errors. False otherwise.
     *
     * @return bool
     */
    public function hasErrors();
}
