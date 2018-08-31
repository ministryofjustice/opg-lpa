<?php

namespace Opg\Lpa\DataModel\User;

use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\EmailAddress;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Validator\Constraints as Assert;
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
            new Assert\NotBlank,
            new Assert\Type([
                'type' => 'xdigit'
            ]),
            new Assert\Length([
                'min' => 32,
                'max' => 32
            ]),
        ]);

        $metadata->addPropertyConstraints('createdAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('updatedAt', [
            new Assert\NotBlank,
            new Assert\Custom\DateTimeUTC,
        ]);

        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank,
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Name'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('address', [
            new Assert\Type([
                'type' => '\Opg\Lpa\DataModel\Common\Address'
            ]),
            new ValidConstraintSymfony,
        ]);

        $metadata->addPropertyConstraints('dob', [
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
            case 'updatedAt':
            case 'createdAt':
                return (($v instanceof \DateTime || is_null($v)) ? $v : new \DateTime($v));
            case 'name':
                return (($v instanceof Name || is_null($v)) ? $v : new Name($v));
            case 'address':
                return (($v instanceof Address || is_null($v)) ? $v : new Address($v));
            case 'dob':
                return (($v instanceof Dob || is_null($v)) ? $v : new Dob($v));
            case 'email':
                return (($v instanceof EmailAddress || is_null($v)) ? $v : new EmailAddress($v));
        }

        return parent::map($property, $v);
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
