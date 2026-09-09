<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

class SharedSpaceNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Shared space not found');
    }
}
