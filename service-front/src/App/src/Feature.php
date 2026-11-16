<?php

declare(strict_types=1);

namespace App;

use function getenv;

enum Feature: string
{
    case OneLogin        = 'ONELOGIN_ENABLED';
    case SharedSpace     = 'SHARED_SPACES_ENABLED';
    case CypressFixtures = 'CYPRESS_FIXTURES_ENABLED';
    case EnforceConflict = 'ENFORCE_CONFLICT_ENABLED';
    case PasswordSignIn  = 'PASSWORD_SIGNIN_ENABLED'; // pragma: allowlist secret

    public function isEnabled(): bool
    {
        return getenv($this->value) === 'true';
    }
}
