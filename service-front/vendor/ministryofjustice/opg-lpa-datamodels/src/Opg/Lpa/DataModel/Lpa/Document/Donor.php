<?php

namespace Opg\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\LongName;
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
     * Field length constants
     */
    const OTHER_NAMES_MIN_LENGTH = 1;
    const OTHER_NAMES_MAX_LENGTH = 50;

    /**
     * @var LongName Their name.
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
                'type' => '\Opg\Lpa\DataModel\Common\LongName'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('otherNames', [
            new Assert\Type([
                'type' => 'string'
            ]),
            new Assert\Length([
                'min' => self::OTHER_NAMES_MIN_LENGTH,
                'max' => self::OTHER_NAMES_MAX_LENGTH,
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
                return ($v instanceof LongName ? $v : new LongName($v));
            case 'address':
                return ($v instanceof Address ? $v : new Address($v));
            case 'dob':
                return (($v instanceof Dob || is_null($v)) ? $v : new Dob($v));
            case 'email':
                return (($v instanceof EmailAddress || is_null($v)) ? $v : new EmailAddress($v));
        }

        return parent::map($property, $v);
    }

    /**
     * @return LongName
     */
    public function getName(): LongName
    {
        return $this->name;
    }

    /**
     * @param LongName $name
     * @return $this
     */
    public function setName(LongName $name): Donor
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getOtherNames()
    {
        return $this->otherNames;
    }

    /**
     * @param string $otherNames
     * @return $this
     */
    public function setOtherNames($otherNames): Donor
    {
        $this->otherNames = $otherNames;

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
    public function setAddress(Address $address): Donor
    {
        $this->address = $address;

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
    public function setDob(Dob $dob): Donor
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * @return EmailAddress
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param EmailAddress $email
     * @return $this
     */
    public function setEmail($email): Donor
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCanSign(): bool
    {
        return $this->canSign;
    }

    /**
     * @param bool $canSign
     * @return $this
     */
    public function setCanSign(bool $canSign): Donor
    {
        $this->canSign = $canSign;

        return $this;
    }
}
