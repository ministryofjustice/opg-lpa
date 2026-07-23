<?php

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

/**
 * Thrown when a user tries to create a shared space while already being
 * a member of one (a user may only be a member of one shared space at a time).
 */
class UserAlreadyInSharedSpaceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('User is already a member of a shared space');
    }
}
