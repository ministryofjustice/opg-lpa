<?php

namespace MakeShared\DataModel\Lpa\Document;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Represents the Donor associated with the LPA.
 *
 * Class Donor
 * @package MakeShared\DataModel\Lpa\Document
 */
class Donor extends AbstractData
{
    /**
     * Field length constants
     */
    private const OTHER_NAMES_MIN_LENGTH = 0;
    private const OTHER_NAMES_MAX_LENGTH = 50;

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
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\LongName'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('otherNames', [
            new Assert\Type('string'),
            new Assert\Length(
                min: self::OTHER_NAMES_MIN_LENGTH,
                max: self::OTHER_NAMES_MAX_LENGTH,
            ),
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Address'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('dob', [
            new Assert\NotBlank(),
            new Assert\Type('\MakeShared\DataModel\Common\Dob'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('email', [
            new Assert\Type('\MakeShared\DataModel\Common\EmailAddress'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('canSign', [
            new Assert\NotNull(),
            new Assert\Type('bool'),
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
                return ($value instanceof LongName ? $value : new LongName($value));
            case 'address':
                return ($value instanceof Address ? $value : new Address($value));
            case 'dob':
                return (($value instanceof Dob || is_null($value)) ? $value : new Dob($value));
            case 'email':
                return (($value instanceof EmailAddress || is_null($value)) ? $value : new EmailAddress($value));
        }

        return parent::map($property, $value);
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
