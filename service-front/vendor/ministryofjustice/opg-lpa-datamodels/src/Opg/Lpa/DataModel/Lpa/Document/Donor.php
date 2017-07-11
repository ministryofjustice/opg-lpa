<?php

namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
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
     * @var Name Their name.
     */
    protected $name;

    /**
     * @var string Any other/past names they've may be known as.
     */
    protected $otherNames;

    /**
     * @var Address Their postal address.
     */
    protected $address;

    /**
     * @var Dob Their date of birth.
     */
    protected $dob;

    /**
     * @var EmailAddress Their email address.
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
                'type' => '\Opg\Lpa\DataModel\Common\Name'
            ]),
            new ValidConstraintSymfony,
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
                'type' => '\Opg\Lpa\DataModel\Common\Address'
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

        $metadata->addPropertyConstraints('email', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\EmailAddress'
            ]),
            new ValidConstraintSymfony,
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
                return ($v instanceof Name ? $v : new Name($v));
            case 'address':
                return ($v instanceof Address ? $v : new Address($v));
            case 'dob':
                return (($v instanceof Dob || is_null($v)) ? $v : new Dob($v));
            case 'email':
                return (($v instanceof EmailAddress || is_null($v)) ? $v : new EmailAddress($v));
        }

        return parent::map($property, $v);
    }
}
