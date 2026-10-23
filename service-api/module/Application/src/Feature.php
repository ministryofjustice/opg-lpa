<?php

declare(strict_types=1);

namespace Application;

use function getenv;

enum Feature: string
{
    case OneLogin = 'ONELOGIN_ENABLED';

    public function isEnabled(): bool
    {
        return getenv($this->value) === 'true';
    }
}
