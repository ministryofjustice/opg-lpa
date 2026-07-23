<?php

declare(strict_types=1);

namespace App;

enum Feature: string
{
    case OneLogin = 'ONELOGIN_ENABLED';

    public function isEnabled(): bool
    {
        return getenv($this->value) === 'true';
    }
}
