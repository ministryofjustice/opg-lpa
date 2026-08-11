<?php

declare(strict_types=1);

namespace Application\Model\Entity;

use DateTimeInterface;

readonly class MemberInvite
{
    public function __construct(
        public string $userId,
        public string $sharedSpaceId,
        public string $firstNames,
        public string $lastName,
        public string $email,
        public bool $isAdmin,
        public string $code,
        public DateTimeInterface $created,
        public DateTimeInterface $expires,
        public ?int $id = null,
    ) {
    }
}
