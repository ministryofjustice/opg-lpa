<?php

namespace MakeShared\DataModel\SharedSpace;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Validator\Constraints as ValidatorConstraints;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;

/**
 * Represents a single user's membership of a shared space
 */
class SharedSpaceMember extends AbstractData
{
    protected string $sharedSpaceId;
    protected string $userId;
    protected string $sharedSpaceName;
    protected bool $isAdmin = false;
    protected bool $isActive = true;
    protected DateTime $createdAt;
    protected ?DateTime $lastLoginAt = null;
    protected Name $name;
    protected string $email;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraints('sharedSpaceId', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Type('xdigit'),
            new ValidatorConstraints\Length(
                min: 32,
                max: 32
            ),
        ]);

        $metadata->addPropertyConstraints('userId', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Type('xdigit'),
            new ValidatorConstraints\Length(
                min: 32,
                max: 32
            ),
        ]);

        $metadata->addPropertyConstraints('sharedSpaceName', [
            new ValidatorConstraints\NotBlank(),
        ]);

        $metadata->addPropertyConstraints('name', [
            new ValidatorConstraints\NotBlank(),
        ]);

        $metadata->addPropertyConstraints('email', [
            new ValidatorConstraints\NotBlank(),
        ]);

        $metadata->addPropertyConstraints('isAdmin', [
            new ValidatorConstraints\Type('bool'),
        ]);

        $metadata->addPropertyConstraints('isActive', [
            new ValidatorConstraints\Type('bool'),
        ]);

        $metadata->addPropertyConstraints('createdAt', [
            new ValidatorConstraints\NotBlank(),
            new ValidatorConstraints\Custom\DateTimeUTC(),
        ]);

        $metadata->addPropertyConstraints('lastLoginAt', [
            new ValidatorConstraints\Custom\DateTimeUTC(),
        ]);
    }

    /**
     * Map property values to their correct type.
     *
     * @param string $property string Property name
     * @param mixed $value mixed Value to map.
     * @return mixed Mapped value.
     */
    protected function map($property, $value): mixed
    {
        return match ($property) {
            'lastLoginAt', 'createdAt' => (($value instanceof DateTime || is_null($value)) ? $value : new DateTime($value)),
            'name' => (($value instanceof Name || is_null($value)) ? $value : new Name($value)),
            default => parent::map($property, $value),
        };
    }

    public function getSharedSpaceId(): string
    {
        return $this->sharedSpaceId;
    }

    public function setSharedSpaceId(string $sharedSpaceId): SharedSpaceMember
    {
        $this->sharedSpaceId = $sharedSpaceId;

        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): SharedSpaceMember
    {
        $this->userId = $userId;

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): SharedSpaceMember
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): SharedSpaceMember
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): SharedSpaceMember
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSharedSpaceName(): string
    {
        return $this->sharedSpaceName;
    }

    public function setSharedSpaceName(string $sharedSpaceName): SharedSpaceMember
    {
        $this->sharedSpaceName = $sharedSpaceName;
        return $this;
    }

    public function setName(Name $name): SharedSpaceMember
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setEmail(string $email): SharedSpaceMember
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setLastLoginAt(?DateTime $lastLoginAt): SharedSpaceMember
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getLastLoginAt(): ?DateTime
    {
        return $this->lastLoginAt;
    }
}
