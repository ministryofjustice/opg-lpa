<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use DateTime;
use DateTimeInterface;

readonly class MemberInvite
{
    public function __construct(
        public ?int $id,
        public string $userId,
        public string $sharedSpaceId,
        public string $firstNames,
        public string $lastName,
        public string $email,
        public bool $isAdmin,
        public string $code,
        public DateTimeInterface $created,
        public DateTimeInterface $expires,
    ) {
    }

    public static function create(
        string $userId,
        string $sharedSpaceId,
        string $firstNames,
        string $lastName,
        string $email,
        bool $isAdmin,
        string $code,
        DateTimeInterface $created,
        DateTimeInterface $expires,
    ): MemberInvite {
        return new MemberInvite(null, $userId, $sharedSpaceId, $firstNames, $lastName, $email, $isAdmin, $code, $created, $expires);
    }

    public function isExpired(): bool
    {
        return $this->expires->getTimestamp() < (new DateTime())->getTimestamp();
    }

    /**
     * Member invite data can be deleted after 30 days.
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        $days = $this->created->diff(new DateTime())->days;

        return $days && $days >= 30;
    }
}
