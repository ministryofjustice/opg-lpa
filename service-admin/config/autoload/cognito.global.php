<?php

declare(strict_types=1);

return [
    'cognito' => [
        'base_url'   => getenv('COGNITO_BASE_URL') ?: null,
        'issuer'     => getenv('COGNITO_ISSUER') ?: null,
        'client_id'  => getenv('COGNITO_CLIENT_ID') ?: null,
        'logout_url' => getenv('COGNITO_LOGOUT_URL') ?: null,
        'dev_email'  => getenv('COGNITO_DEV_EMAIL') ?: 'dev-admin@local',
    ],
];
