<?php

namespace MakeShared\OneLogin;

class LinkReason
{
    /**
     * The Make account is already linked to a *different* One Login identity. */
    public const string ALREADY_LINKED = 'already-linked';

    /** No Make account exists for the supplied email address. */
    public const string ACCOUNT_NOT_FOUND = 'account-not-found';

    /** A Make account existed for the email address but has been deleted. */
    public const string ACCOUNT_DELETED = 'account-deleted';

    /** The email address exists but the password was wrong. */
    public const string INVALID_CREDENTIALS = 'invalid-credentials';

    /** The account is temporarily locked after too many failed sign-in attempts. */
    public const string ACCOUNT_LOCKED = 'account-locked';

    /** The account has not been activated yet. */
    public const string ACCOUNT_NOT_ACTIVE = 'account-not-active';
}
