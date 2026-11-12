<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use DateInterval;
use DateTime;
use DateTimeImmutable;
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
    ): MemberInvite {
        $code = sprintf('%08d', random_int(0, 99999999));
        $created = new DateTimeImmutable('now');
        $expires = $created->add(DateInterval::createFromDateString('7 days'));

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
