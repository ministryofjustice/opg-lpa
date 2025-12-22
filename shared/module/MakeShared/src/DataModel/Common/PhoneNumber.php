<?php

namespace MakeShared\DataModel\Common;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as SymfonyConstraints;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a phone number.
 *
 * Class PhoneNumber
 * @package MakeShared\DataModel\Common
 */
class PhoneNumber extends AbstractData
{
    /**
     * @var string A phone number.
     */
    protected $number;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // As there is only 1 property, include NotBlank as there is no point this object existing without it.
        // Regex taken from: https://github.com/Respect/Validation/blob/master/library/Rules/Phone.php
        $metadata->addPropertyConstraints('number', [
            new SymfonyConstraints\NotBlank(),
            new SymfonyConstraints\Regex(
                // a fairly loose regex, it allows for country codes plus between
                // 8 and 15 numbers/spaces
                pattern: '/^[+|0]?[0-9 ]{8,15}$/',
                message: 'invalid-phone-number',
            ),
        ]);
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function setNumber(string $number): PhoneNumber
    {
        $this->number = $number;

        return $this;
    }
}
