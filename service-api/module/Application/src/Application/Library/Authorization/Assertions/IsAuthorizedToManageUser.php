<?php
namespace Application\Library\Authorization\Assertions;

use ZfcRbac\Assertion\AssertionInterface;
use ZfcRbac\Service\AuthorizationService;

/**
 * At the moment there is a 1-to-1 map between authorized user and route user.
 * i.e. A user can only manage their own LPAs.
 *
 * Class IsAuthorizedToManageUser
 * @package Application\Library\Authorization\Assertions
 */
class IsAuthorizedToManageUser implements AssertionInterface {

    public function assert(AuthorizationService $authorization, $routeUser = null ){

        // We can only authorize is there's a route user...
        if( !is_string($routeUser) ){ return false; }

        //---

        $tokenUser = $authorization->getIdentity();

        // We can only authorize if we can get the user's id from the Identity...
        if( !is_callable( [ $tokenUser, 'id' ] ) ){ return false; }

        // Return true iff the id's match...
        return ($tokenUser->id() === $routeUser);

    } // function

} // class
