<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\AbstractData;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

/**
 * Represents a phone number.
 *
 * Class PhoneNumber
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class PhoneNumber extends AbstractData {

    /**
     * @var string A phone number.
     */
    protected $number;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        // As there is only 1 property, include NotBlank as there is no point this object existing without it.

        // Regex taken from: https://github.com/Respect/Validation/blob/master/library/Rules/Phone.php

        $metadata->addPropertyConstraints('number', [
            new Assert\NotBlank,
            new Assert\Regex([
                // a fairly loose regex, it allows for country codes plus between
                // 8 and 15 numbers/spaces
                'pattern' => '/^[+|0]?[0-9 ]{8,15}$/',
                'message' => 'invalid-phone-number',
            ]),
        ]);

    } // function

} // class
