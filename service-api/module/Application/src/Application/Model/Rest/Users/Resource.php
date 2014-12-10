<?php
namespace Application\Model\Rest\Users;

use Application\Model\Rest\AbstractResource;

use Application\Library\ApiProblem\ApiProblem;

class Resource extends AbstractResource {

    public function getIdentifier(){ return 'userId'; }
    public function getName(){ return 'users'; }

    public function getType(){
        return self::TYPE_COLLECTION;
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch($id){

        $this->checkAccess( $id );

        //------------------------

        /**
         * We should already have the details from the authentication.
         * Whilst using Auth-1, we should just use this.
         */

        // TODO - this data should be fleshed out with the rest of their details.
        $user = new Entity( [ 'id' => $id ] );

        //---

        // Set the user in the AbstractResource so it can be used for route generation.
        $this->setRouteUser( $user );

        //---

        return $user;

    } // function

} // class
