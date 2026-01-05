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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('id', [
            new Assert\NotBlank(
                groups: ['required-at-api']
            ),
            new Assert\Type('int'),
        ]);

        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Name'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Address'),
            new ValidConstraintSymfony(),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): NotifiedPerson
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Name
     */
    public function getName(): Name
    {
        return $this->name;
    }

    /**
     * @param Name $name
     * @return $this
     */
    public function setName(Name $name): NotifiedPerson
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     * @return $this
     */
    public function setAddress(Address $address): NotifiedPerson
    {
        $this->address = $address;

        return $this;
    }
}
