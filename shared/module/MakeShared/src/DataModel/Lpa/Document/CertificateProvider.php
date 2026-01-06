<?php

namespace MakeShared\DataModel\Lpa\Document;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a person representing a Certificate Provider.
 *
 * Class CertificateProvider
 * @package MakeShared\DataModel\Lpa\Document
 */
class CertificateProvider extends AbstractData
{
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
    public function setName(Name $name): CertificateProvider
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
    public function setAddress(Address $address): CertificateProvider
    {
        $this->address = $address;

        return $this;
    }
}
