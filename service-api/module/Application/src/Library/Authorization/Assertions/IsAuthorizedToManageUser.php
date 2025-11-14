<?php

namespace Application\Library\Authorization\Assertions;

use Lmc\Rbac\Assertion\AssertionInterface;
use Lmc\Rbac\Identity\IdentityInterface;

/**
 * The authorized user or another service can manage the user data and LPAs
 *
 * Class IsAuthorizedToManageUser
 * @package Application\Library\Authorization\Assertions
 * @psalm-api
 */
class IsAuthorizedToManageUser implements AssertionInterface
{
    public function assert(string $permission, ?IdentityInterface $identity = null, mixed $context = null): bool
    {
        // We can only authorize is there's a route user...
        if (!is_string($context)) {
            return false;
        }

        //  Otherwise we can only authorize if we can get the user's id from the Identity...
        if (!is_callable([$identity, 'id'])) {
            return false;
        }

        // Return true iff the id's match...
        return ($identity->id() === $context);
    }
}
