<?php

namespace MakeShared\DataModel\User;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Validator\Constraints as ValidatorConstraints;
use Symfony\Component\Validator\Constraints\Valid as ValidConstraintSymfony;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;

/**
 * Represents a user of the LPA platform.
 *
 * Class User
 */
class User extends AbstractData
{
    /**
     * @var string The user's internal ID.
     */
    protected $id;

    /**
     * @var DateTime the user was created.
     */
    protected $createdAt;

    /**
     * @var DateTime the user was last updated.
     */
    protected $updatedAt;

    /**
     * @var Name Their name.
     */
    protected $name;

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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('id', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Type('xdigit'),
            new ValidatorConstraints\Length(
                min: 32,
                max: 32
            ),
        ]);

        $metadata->addPropertyConstraints('createdAt', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Custom\DateTimeUTC(),
        ]);

        $metadata->addPropertyConstraints('updatedAt', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Custom\DateTimeUTC(),
        ]);

        $metadata->addPropertyConstraints('name', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Type('\MakeShared\DataModel\Common\Name'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('address', [
            new ValidatorConstraints\Type('\MakeShared\DataModel\Common\Address'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('dob', [
            new ValidatorConstraints\Type('\MakeShared\DataModel\Common\Dob'),
            new ValidConstraintSymfony(),
        ]);

        $metadata->addPropertyConstraints('email', [
            new ValidatorConstraints\Type('\MakeShared\DataModel\Common\EmailAddress'),
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
            case 'updatedAt':
            case 'createdAt':
                return (($value instanceof \DateTime || is_null($value)) ? $value : new \DateTime($value));
            case 'name':
                return (($value instanceof Name || is_null($value)) ? $value : new Name($value));
            case 'address':
                return (($value instanceof Address || is_null($value)) ? $value : new Address($value));
            case 'dob':
                return (($value instanceof Dob || is_null($value)) ? $value : new Dob($value));
            case 'email':
                return (($value instanceof EmailAddress || is_null($value)) ? $value : new EmailAddress($value));
        }

        return parent::map($property, $value);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId(string $id): User
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(DateTime $updatedAt): User
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Name|null
     */
    public function getName(): Name|null
    {
        return $this->name;
    }

    /**
     * @param Name $name
     * @return $this
     */
    public function setName(Name $name): User
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param Address $address
     * @return $this
     */
    public function setAddress($address): User
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Dob
     */
    public function getDob()
    {
        return $this->dob;
    }

    /**
     * @param Dob $dob
     * @return $this
     */
    public function setDob($dob): User
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
    public function setEmail($email): User
    {
        $this->email = $email;

        return $this;
    }
}
