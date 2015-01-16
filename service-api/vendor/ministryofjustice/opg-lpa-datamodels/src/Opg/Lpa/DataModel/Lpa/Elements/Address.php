<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\AbstractData;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a postal address.
 *
 * Class Address
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Address extends AbstractData {

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

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('address1', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 50 ]),
        ]);

        $metadata->addPropertyConstraints('address2', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 50 ]),
        ]);

        $metadata->addPropertyConstraints('address3', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 50 ]),
        ]);

        $metadata->addPropertyConstraints('address3', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'max' => 50 ]),
        ]);

        // This could be improved, but we'd need to be very careful not to block valid postcodes.
        $metadata->addPropertyConstraints('postcode', [
            new Assert\Type([ 'type' => 'string' ]),
            new Assert\Length([ 'min' => 5, 'max' => 8 ]),
        ]);

        //---

        // We required either address2 OR postcode to be set for an address to be considered valid.
        $metadata->addConstraint( new Assert\Callback(function ($object, ExecutionContextInterface $context){

            if( empty($object->address2) && empty($object->postcode) ){
                $context->buildViolation('address2-or-postcode-required')->addViolation();
            }

        }));

    } // function

} // class
