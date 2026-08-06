<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

class MemberNotInSharedSpaceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Member not found in shared space');
    }
}
