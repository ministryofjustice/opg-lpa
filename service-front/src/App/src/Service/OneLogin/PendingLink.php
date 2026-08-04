<?php

declare(strict_types=1);

namespace App\Service\OneLogin;

readonly class PendingLink
{
    public function __construct(
        public string $sub,
        public string $email,
    ) {
    }
}
