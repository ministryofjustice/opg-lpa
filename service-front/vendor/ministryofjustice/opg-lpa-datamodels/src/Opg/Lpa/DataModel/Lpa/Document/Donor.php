<?php

namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Elements;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents the Donor associated with the LPA.
 *
 * Class Donor
 * @package Opg\Lpa\DataModel\Lpa\Document
 */
class Donor extends AbstractData
{
    /**
     * @var Elements\Name Their name.
     */
    protected $name;

    /**
     * @var string Any other/past names they've may be known as.
     */
    protected $otherNames;

    /**
     * @var Elements\Address Their postal address.
     */
    protected $address;

    /**
     * @var Elements\Dob Their date of birth.
     */
    protected $dob;

    /**
     * @var Elements\EmailAddress Their email address.
     */
    protected $email;

    /**
     * @var bool Can the donor sign the form themselves.
     */
    protected $canSign;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Name'
            ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('otherNames', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => 1,
                'max' => 50
            ]),
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Elements\Address'
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

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Lpa\Elements\EmailAddress'
            ]),
            new Assert\Valid,
        ]);

        $metadata->addPropertyConstraints('canSign', [
            new Assert\NotNull,
            new Assert\Type([
                'type' => 'bool'
            ]),
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
            case 'address':
                return ($v instanceof Elements\Address ? $v : new Elements\Address($v));
            case 'dob':
                return (($v instanceof Elements\Dob || is_null($v)) ? $v : new Elements\Dob($v));
            case 'email':
                return (($v instanceof Elements\EmailAddress || is_null($v)) ? $v : new Elements\EmailAddress($v));
        }

        return parent::map($property, $v);
    }
}
