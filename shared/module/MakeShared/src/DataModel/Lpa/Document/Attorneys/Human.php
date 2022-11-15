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

    /**
     * @return void
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Type([
                'type' => '\MakeShared\DataModel\Common\Name'
            ]),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('dob', [
            new Assert\NotBlank(),
            new Assert\Type([
                'type' => '\MakeShared\DataModel\Common\Dob'
            ]),
            new ValidConstraintSymfony(),
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

    /**
     * @return (mixed|string)[]
     *
     * @psalm-return array{type: 'human'}
     */
    public function toArray(bool $retainDateTimeInstances = false)
    {
        return array_merge(parent::toArray($retainDateTimeInstances), [
            'type' => 'human'
        ]);
    }
}
