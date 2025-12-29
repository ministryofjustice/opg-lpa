<?php

namespace MakeShared\DataModel\Lpa\Document\Attorneys;

use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a Human Attorney.
 *
 * Class Human
 * @package MakeShared\DataModel\Lpa\Document\Attorney
 */
class Human extends AbstractAttorney
{
    /**
     * @var Name Their name.
     */
    protected $name;

    /**
     * @var Dob Their date of birth.
     */
    protected $dob;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Name'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('dob', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Dob'),
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
            case 'dob':
                return ($value instanceof Dob ? $value : new Dob($value));
        }

        return parent::map($property, $value);
    }

    public function toArray(bool $retainDateTimeInstances = false)
    {
        return array_merge(parent::toArray($retainDateTimeInstances), [
            'type' => 'human'
        ]);
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
    public function setName(Name $name): Human
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Dob
     */
    public function getDob(): Dob
    {
        return $this->dob;
    }

    /**
     * @param Dob $dob
     * @return $this
     */
    public function setDob(Dob $dob): Human
    {
        $this->dob = $dob;

        return $this;
    }
}
