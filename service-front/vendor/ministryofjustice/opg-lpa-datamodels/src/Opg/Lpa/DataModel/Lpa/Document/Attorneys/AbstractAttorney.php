<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;
use Opg\Lpa\DataModel\AbstractData;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Base Represents of an Attorney. This can be extended with one of two types, either Human or TrustCorporation.
 *
 * Class AbstractAttorney
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorneys
 */
abstract class AbstractAttorney extends AbstractData {

    /**
     * @var int The attorney's internal ID.
     */
    protected $id;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    /**
     * @var Elements\EmailAddress Their email address.
     */
    protected $email;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => 'int' ]),
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Address' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\EmailAddress' ]),
            new Assert\Valid,
        ]);

    } // function

    //------------------------------------------------

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map( $property, $v ){

        switch( $property ){
            case 'address':
                return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
            case 'email':
                return ($v instanceof Elements\EmailAddress) ? $v : new Elements\EmailAddress( $v );
        }

        // else...
        return parent::map( $property, $v );

    } // function

} // abstract class
