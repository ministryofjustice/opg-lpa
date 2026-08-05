<?php

namespace MakeShared\DataModel\SharedSpace;

use MakeShared\DataModel\AbstractData;
use MakeShared\DataModel\Validator\Constraints as ValidatorConstraints;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use DateTime;

/**
 * Represents a single user's membership of a shared space - i.e. a row in
 * the shared_space_members table.
 */
class SharedSpaceMember extends AbstractData
{
    protected string $sharedSpaceId;
    protected string $userId;
    protected bool $isAdmin = false;
    protected bool $isActive = true;
    protected DateTime $createdAt;

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
            'createdAt' => (($value instanceof DateTime || is_null($value)) ? $value : new DateTime($value)),
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

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): SharedSpaceMember
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getIsActive(): bool
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
}
