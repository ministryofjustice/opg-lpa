<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use RuntimeException;

/**
 * Thrown when an admin tries to delete their account while other members of
 * their shared space remain and none of them is an admin.
 */
class NoOtherAdminException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No other admin');
    }
}
