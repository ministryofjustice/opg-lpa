<?php
namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;

/**
 * Represents a person to be notified.
 *
 * Class NotifiedPerson
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class NotifiedPerson extends AbstractData {

    /**
     * @var int The person's internal ID.
     */
    protected $id;

    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    //------------------------------------------------

    public static function loadValidatorMetadata(ClassMetadata $metadata){

        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank([ 'groups' => ['require-actor-ids'] ]),
            new Assert\Type([ 'type' => 'int' ]),
        ]);

        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Name' ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Type([ 'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Address' ]),
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
            case 'name':
                return ($v instanceof Elements\Name) ? $v : new Elements\Name( $v );
            case 'address':
                return ($v instanceof Elements\Address) ? $v : new Elements\Address( $v );
        }

        // else...
        return parent::map( $property, $v );

    } // function

} // class
