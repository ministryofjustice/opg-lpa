<?php

declare(strict_types=1);

return [
    'alb' => [
        // Base URL to fetch the ALB's public signing key(s) from, by "kid". In production
        // this is AWS's regional endpoint (https://public-keys.auth.elb.<region>.amazonaws.com);
        // in local development it points at the mock ALB/Cognito server instead, so the
        // exact same verification code path runs in both environments.
        'public_key_base_url' => getenv('OPG_ALB_PUBLIC_KEY_BASE_URL') ?: null,
        // The ARN of the admin ALB, checked against the "signer" field of the
        // X-Amzn-Oidc-Data JWT header as a defence against spoofed headers.
        'admin_arn'           => getenv('OPG_ADMIN_ALB_ARN') ?: null,
        // Name of the ALB's own auth session cookie (set via session_cookie_name on the
        // authenticate-oidc listener action). Must be expired on sign-out, since the ALB
        // caches the authenticated session independently of Cognito's own session.
        'session_cookie_name' => getenv('OPG_ADMIN_ALB_SESSION_COOKIE_NAME') ?: null,
    ],
];
