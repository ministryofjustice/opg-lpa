<?php

namespace Application\Library\Authorization\Assertions;

use Application\Library\Authentication\Identity\AuthService;
use ZfcRbac\Assertion\AssertionInterface;
use ZfcRbac\Service\AuthorizationService;

/**
 * The authorized user or another service can manage the user data and LPAs
 *
 * Class IsAuthorizedToManageUser
 * @package Application\Library\Authorization\Assertions
 */
class IsAuthorizedToManageUser implements AssertionInterface
{
    public function assert(AuthorizationService $authorization, $routeUserId = null)
    {
        // We can only authorize is there's a route user...
        if (!is_string($routeUserId)) {
            return false;
        }

        $tokenUser = $authorization->getIdentity();

        //  If the token user is a service then allow it
        if ($tokenUser instanceof AuthService) {
            return true;
        }

        //  Otherwise we can only authorize if we can get the user's id from the Identity...
        if (!is_callable([$tokenUser, 'id'])) {
            return false;
        }

        // Return true iff the id's match...
        return ($tokenUser->id() === $routeUserId);
    }
}
