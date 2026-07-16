<?php

declare(strict_types=1);

return [
    'cognito' => [
        'base_url'   => getenv('OPG_COGNITO_BASE_URL') ?: null,
        'client_id'   => getenv('OPG_COGNITO_CLIENT_ID') ?: null,
        'issuer'     => getenv('OPG_COGNITO_ISSUER') ?: null,
        'logout_url' => getenv('OPG_COGNITO_LOGOUT_URL') ?: null,
        'test_username'  => getenv('OPG_COGNITO_TEST_USERNAME') ?: 'seeded_test_user',
    ],
];
