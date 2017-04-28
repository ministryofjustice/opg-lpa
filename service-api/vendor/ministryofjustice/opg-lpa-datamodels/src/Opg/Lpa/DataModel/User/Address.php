<?php

namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a postal address.
 *
 * Class Address
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Address extends AbstractData
{
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('address1', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => 50
            ]),
        ]);

        $metadata->addPropertyConstraints('address2', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => 50
            ]),
        ]);

        $metadata->addPropertyConstraints('address3', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => 50
            ]),
        ]);

        // This could be improved, but we'd need to be very careful not to block valid postcodes.
        $metadata->addPropertyConstraints('postcode', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => 1,
                'max' => 8
            ]),
        ]);
    }

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
