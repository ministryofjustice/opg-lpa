<?php

declare(strict_types=1);

namespace Application\Model\Service\Session;

class ContainerNamespace
{
    // Used to track whether the user has accepted the terms and conditions
    public const string TERMS_AND_CONDITIONS_CHECK = 'TermsAndConditionsCheck';

    // Used to record the URL being requested before authentication
    public const string PRE_AUTH_REQUEST = 'PreAuthRequest';

    // Used to record the context around an authentication failure
    public const string AUTH_FAILURE_REASON = 'AuthFailureReason';

    // Used to store user and identity details after authentication
    public const string USER_DETAILS = 'UserDetails';
}
