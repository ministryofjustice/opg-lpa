<?php

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

class InviteEmailMismatchException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invite email does not match user email');
    }
}
