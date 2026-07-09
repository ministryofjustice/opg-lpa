<?php

namespace Application\Library\Authentication\Identity;

/**
 * Identity for the admin service making service-to-service calls.
 * Authenticated via a pre-shared secret rather than a user token.
 * Network-level security (VPC security groups) is the primary control;
 * this credential provides an additional explicit identity for audit purposes.
 */
class AdminService extends AbstractIdentity
{
    protected $roles = ['admin-service'];
}
