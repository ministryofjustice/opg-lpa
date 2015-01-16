<?php
namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an email address.
 *
 * Class EmailAddress
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class EmailAddress extends AbstractData {

    /**
     * @var string An email address.
     */
    protected $address;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        // As there is only 1 property, include NotBlank as there is no point this object existing without it.

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Email([ 'strict' => true ])
        ]);

    }

} // class
