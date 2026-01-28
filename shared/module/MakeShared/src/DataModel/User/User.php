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

class User extends AbstractData
{
    protected ?string $id = null;
    protected ?DateTime $createdAt = null;
    protected ?DateTime $updatedAt = null;
    protected ?Name $name = null;
    protected ?Address $address = null;
    protected ?Dob $dob = null;
    protected ?EmailAddress $email = null;
    protected ?DateTime $lastLoginAt = null;
    protected ?int $numberOfLpas = null;

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

        $metadata->addPropertyConstraints('lastLoginAt', [
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

        $metadata->addPropertyConstraints('numberOfLpas', [
            new ValidatorConstraints\Type('integer'),
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
        return match ($property) {
            'updatedAt', 'createdAt', 'lastLoginAt' => (($value instanceof \DateTime || is_null($value)) ? $value : new \DateTime($value)),
            'name' => (($value instanceof Name || is_null($value)) ? $value : new Name($value)),
            'address' => (($value instanceof Address || is_null($value)) ? $value : new Address($value)),
            'dob' => (($value instanceof Dob || is_null($value)) ? $value : new Dob($value)),
            'email' => (($value instanceof EmailAddress || is_null($value)) ? $value : new EmailAddress($value)),
            default => parent::map($property, $value),
        };
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): User
    {
        $this->id = $id;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): User
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getName(): ?Name
    {
        return $this->name;
    }

    public function setName(Name $name): User
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): User
    {
        $this->address = $address;

        return $this;
    }

    public function getDob(): ?Dob
    {
        return $this->dob;
    }

    public function setDob(?Dob $dob): User
    {
        $this->dob = $dob;

        return $this;
    }

    public function getEmail(): ?EmailAddress
    {
        return $this->email;
    }

    public function setEmail(?EmailAddress $email): User
    {
        $this->email = $email;

        return $this;
    }

    public function getLastLoginAt(): ?DateTime
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTime $lastLoginAt): User
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getNumberOfLpas(): ?int
    {
        return $this->numberOfLpas;
    }

    public function setNumberOfLpas(?int $numberOfLpas): User
    {
        $this->numberOfLpas = $numberOfLpas;
        return $this;
    }
}
