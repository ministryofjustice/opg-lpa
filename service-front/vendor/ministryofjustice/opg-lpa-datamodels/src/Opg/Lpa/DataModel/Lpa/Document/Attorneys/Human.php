<?php

namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Elements;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents a Human Attorney.
 *
 * Class Human
 * @package Opg\Lpa\DataModel\Lpa\Document\Attorney
 */
class Human extends AbstractAttorney
{
    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var Elements\Dob Their date of birth.
     */
    protected $dob;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Name'
            ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('dob', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Dob'
            ]),
            new Assert\Valid,
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
                return ($v instanceof Elements\Name ? $v : new Elements\Name($v));
            case 'dob':
                return ($v instanceof Elements\Dob ? $v : new Elements\Dob($v));
        }

        return parent::map($property, $v);
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'type' => 'human'
        ]);
    }
}
