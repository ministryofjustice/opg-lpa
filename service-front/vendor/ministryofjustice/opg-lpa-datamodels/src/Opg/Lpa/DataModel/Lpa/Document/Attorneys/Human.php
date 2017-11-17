<?php

namespace Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
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
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Name'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('dob', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Dob'
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
            case 'dob':
                return ($v instanceof Dob ? $v : new Dob($v));
        }

        return parent::map($property, $v);
    }

    public function toArray(callable $mongoDateCallback = null)
    {
        return array_merge(parent::toArray($mongoDateCallback), [
            'type' => 'human'
        ]);
    }
}
