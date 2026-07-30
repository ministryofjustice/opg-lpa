<?php

declare(strict_types=1);

namespace App;

use function getenv;

enum Feature: string
{
    case OneLogin        = 'ONELOGIN_ENABLED';
    case SharedSpace     = 'SHARED_SPACES_ENABLED';
    case CypressFixtures = 'CYPRESS_FIXTURES_ENABLED';

    public function isEnabled(): bool
    {
        return getenv($this->value) === 'true';
    }
}
