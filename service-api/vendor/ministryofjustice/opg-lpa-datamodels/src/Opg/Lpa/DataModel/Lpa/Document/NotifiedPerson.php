<?php

namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a person to be notified.
 *
 * Class NotifiedPerson
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class NotifiedPerson extends AbstractData
{
    /**
     * @var int The person's internal ID.
     */
    protected $id;

    /**
     * @var Name Their name.
     */
    protected $name;

    /**
     * @var Address Their postal address.
     */
    protected $address;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank([
                'groups' => ['required-at-api']
            ]),
            new Assert\Type([
                'type' => 'int'
            ]),
        ]);

        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Name'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Address'
            ]),
            new ValidConstraintSymfony,
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $v mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $v)
    {
        switch ($property) {
            case 'name':
                return ($v instanceof Name ? $v : new Name($v));
            case 'address':
                return ($v instanceof Address ? $v : new Address($v));
        }

        return parent::map($property, $v);
    }
}
