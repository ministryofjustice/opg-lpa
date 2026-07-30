<?php

declare(strict_types=1);

namespace App;

enum Feature: string
{
    case OneLogin = 'ONELOGIN_ENABLED';
    case SharedSpace = 'SHARED_SPACES_ENABLED';

    public function isEnabled(): bool
    {
        return getenv($this->value) === 'true';
    }
}
