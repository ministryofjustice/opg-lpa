<?php

namespace Opg\Lpa\DataModel\Common;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents an email address.
 *
 * Class EmailAddress
 * @package Opg\Lpa\DataModel\Common
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
            new Assert\NotBlank,
            new Assert\Email([
                'strict' => true
            ]),
            new Assert\Regex([
                'pattern' => '/\.([^.@]{2,63})$/u',
                'message' => 'invalid-email-address'
            ])
        ]);
    }

    /**
     * @return string The email address.
     */
    public function __toString()
    {
        return $this->address;
    }
}
