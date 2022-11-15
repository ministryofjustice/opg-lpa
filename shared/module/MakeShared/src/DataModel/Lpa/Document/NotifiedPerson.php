<?php

namespace MakeShared\DataModel\Lpa\Document;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a person to be notified.
 *
 * Class NotifiedPerson
 * @package MakeShared\DataModel\Lpa\Document
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

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     *
     * @return mixed Mapped value.
     */
    protected function map($property, $value)
    {
        switch ($property) {
            case 'name':
                return ($value instanceof Name ? $value : new Name($value));
            case 'address':
                return ($value instanceof Address ? $value : new Address($value));
        }

        return parent::map($property, $value);
    }
}
