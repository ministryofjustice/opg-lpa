<?php

namespace MakeShared\DataModel\Validator;

use ArrayObject;

/**
 * Represents a response from MakeShared\DataModel\Lpa\ValidatableInterface->validate().
 *
 * Class ValidatorResponse
 * @package MakeShared\DataModel\Validator
 *
 * @psalm-suppress MissingTemplateParam
 */
class ValidatorResponse extends ArrayObject implements ValidatorResponseInterface
{
    /**
     * Return true if this response contains one or more errors. False otherwise.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (count($this) > 0);
    }
}
