<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

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
                'pattern' => '/^[+]?([\d]{0,3})?[\(\.\-\s]?([\d]{1,3})[\)\.\-\s]*(([\d]{3,5})[\.\-\s]?([\d]{4})|([\d]{2}[\.\-\s]?){4})$/',
                'message' => 'invalid-phone-number',
            ]),
        ]);

    } // function

} // class
