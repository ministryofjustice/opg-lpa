<?php

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

class InviteAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invite linked to this email already exists');
    }
}
