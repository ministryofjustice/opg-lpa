<?php

declare(strict_types=1);

namespace App;

final class RequestAttributes
{
    /**
     * The authenticated admin user's email address, set by AuthorizationMiddleware
     * after validating the Cognito OIDC claims from the ALB-injected JWT header.
     */
    public const string USER_EMAIL = 'user_email';

    /**
     * The CSRF token for the current session, set by CsrfMiddleware.
     */
    public const string CSRF_TOKEN = 'csrf_token';

    /**
     * The decoded OIDC claims array from the ALB-injected JWT header, set by AlbOidcMiddleware.
     */
    public const string OIDC_CLAIMS = 'oidc_claims';

    private function __construct()
    {
    }
}
