<?php

namespace MakeShared\DataModel\Common;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents an email address.
 *
 * Class EmailAddress
 * @package MakeShared\DataModel\Common
 */
class EmailAddress extends AbstractData
{
    /**
     * @var string An email address.
     */
    protected $address;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // As there is only 1 property, include NotBlank as there is no point this object existing without it.
        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank(),
            new Assert\Email([
                'strict' => true
            ])
        ]);
    }

    /**
     * @return string The email address.
     */
    public function __toString(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): EmailAddress
    {
        $this->address = $address;

        return $this;
    }
}
