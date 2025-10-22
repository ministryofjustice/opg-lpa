<?php

namespace Application\Library\Authorization\Assertions;

use LmcRbacMvc\Assertion\AssertionInterface;
use LmcRbacMvc\Service\AuthorizationService;

/**
 * The authorized user or another service can manage the user data and LPAs
 *
 * Class IsAuthorizedToManageUser
 * @package Application\Library\Authorization\Assertions
 * @psalm-api
 */
class IsAuthorizedToManageUser implements AssertionInterface
{
    public function assert(AuthorizationService $authorizationService, $routeUserId = null)
    {
        // We can only authorize is there's a route user...
        if (!is_string($routeUserId)) {
            return false;
        }

        $tokenUser = $authorizationService->getIdentity();

        //  Otherwise we can only authorize if we can get the user's id from the Identity...
        if (!is_callable([$tokenUser, 'id'])) {
            return false;
        }

        // Return true iff the id's match...
        return ($tokenUser->id() === $routeUserId);
    }
}
