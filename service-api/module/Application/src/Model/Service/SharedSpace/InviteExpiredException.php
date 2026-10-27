<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

class InviteExpiredException extends RuntimeException
{
    public function __construct(public readonly string $expires)
    {
        parent::__construct('Invite expired');
    }
}
