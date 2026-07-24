<?php

declare(strict_types=1);

namespace Application\Model\Service\OneLogin;

use RuntimeException;

class OneLoginAuthenticationException extends RuntimeException
{
    public function __construct(
        private readonly string $oidcReason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?: $oidcReason, $code, $previous);
    }

    public function reason(): string
    {
        return $this->oidcReason;
    }
}
