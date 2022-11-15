<?php

namespace MakeShared\DataModel\Common;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a postal address.
 *
 * Class Address
 * @package MakeShared\DataModel\Common
 */
class Address extends AbstractData
{
    /**
     * Field length constants
     */
    const ADDRESS_LINE_MAX_LENGTH = 50;//40;
    const POSTCODE_MIN_LENGTH = 1;
    const POSTCODE_MAX_LENGTH = 8;

    /**
     * @var string First line of the address.
     */
    protected $address1;

    /**
     * @var string Second line of the address.
     */
    protected $address2;

    /**
     * @var string Third line of the address.
     */
    protected $address3;

    /**
     * @var string A UK postcode.
     */
    protected $postcode;

    /**
     * Returns a comma separated string representation of the address.
     *
     * @return string
     */
    public function __toString()
    {
        $address  = "{$this->address1}, ";
        $address .= (!empty($this->address2) ? "{$this->address2}, " : '');
        $address .= (!empty($this->address3) ? "{$this->address3}, " : '');
        $address .= (!empty($this->postcode) ? "{$this->postcode}"   : '');

        // Tidy the string up...
        $address = rtrim(trim($address), ',');

        return $address;
    }
}
