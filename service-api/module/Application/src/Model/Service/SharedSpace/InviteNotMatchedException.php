<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

class InviteNotMatchedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invite not matched');
    }
}
