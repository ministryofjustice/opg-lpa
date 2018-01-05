<?php

namespace Opg\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\Callback as CallbackConstraintSymfony;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a postal address.
 *
 * Class Address
 * @package Opg\Lpa\DataModel\Common
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('address1', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::ADDRESS_LINE_MAX_LENGTH
            ]),
        ]);

        $metadata->addPropertyConstraints('address2', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::ADDRESS_LINE_MAX_LENGTH
            ]),
        ]);

        $metadata->addPropertyConstraints('address3', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'max' => self::ADDRESS_LINE_MAX_LENGTH
            ]),
        ]);

        // This could be improved, but we'd need to be very careful not to block valid postcodes.
        $metadata->addPropertyConstraints('postcode', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => self::POSTCODE_MIN_LENGTH,
                'max' => self::POSTCODE_MAX_LENGTH,
            ]),
        ]);

        // We required either address2 OR postcode to be set for an address to be considered valid.
        $metadata->addConstraint(new CallbackConstraintSymfony(function ($object, ExecutionContextInterface $context) {
            if (empty($object->address2) && empty($object->postcode)) {
                $context->buildViolation((new Assert\NotNull())->message)->atPath('address2/postcode')->addViolation();
            }
        }));
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

    /**
     * @return string
     */
    public function getAddress1(): string
    {
        return $this->address1;
    }

    /**
     * @param string $address1
     * @return $this
     */
    public function setAddress1(string $address1): Address
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param string $address2
     * @return $this
     */
    public function setAddress2($address2): Address
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress3()
    {
        return $this->address3;
    }

    /**
     * @param string $address3
     * @return $this
     */
    public function setAddress3($address3): Address
    {
        $this->address3 = $address3;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode): Address
    {
        $this->postcode = $postcode;

        return $this;
    }
}
