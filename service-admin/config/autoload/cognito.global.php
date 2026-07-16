<?php

declare(strict_types=1);

return [
    'cognito' => [
        // Base URL of the Cognito user pool/app client. In production this is AWS's
        // hosted-UI domain; in local development it points at the mock ALB/Cognito
        // server instead, so the exact same code path runs in both environments.
        'base_url'   => getenv('OPG_COGNITO_BASE_URL') ?: null,
        // The Cognito app client ID, checked against the "client" field of the
        // X-Amzn-Oidc-Data JWT header as a defence against spoofed headers.
        'client_id'   => getenv('OPG_COGNITO_CLIENT_ID') ?: null,
        // Cognito's hosted-UI logout endpoint. Redirecting here clears Cognito's own
        // session, but not the ALB's separately-cached auth session cookie — see
        // alb.session_cookie_name, which must also be expired on sign-out.
        'logout_url' => getenv('OPG_COGNITO_LOGOUT_URL') ?: null,
        // Email used by the mock ALB/Cognito server (see mock-cognito/) to sign in as
        // in local development. Not used in production.
        'test_username'  => getenv('OPG_COGNITO_TEST_USERNAME') ?: 'seeded_test_user',
    ],
];
