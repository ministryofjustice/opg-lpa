<?php
namespace Application\Model\Rest\Users;

use Application\Model\Rest\AbstractResource;

use Opg\Lpa\DataModel\User\User;

use Application\Library\DateTime;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

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

        $user = $this->getCollection( 'user' )->findOne( [ '_id' => $id ] );

        // Ensure user is an array. The above may return null.
        if( !is_array($user) ){ $user = array(); }

        $user = [ 'id' => $id ] + $user;

        $user = new User( $user );

        //------------------------

        $user = new Entity( $user );


        // Set the user in the AbstractResource so it can be used for route generation.
        $this->setRouteUser( $user );

        //---

        return $user;

    } // function


    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|Entity
     */
    public function update($data, $id){

        $this->checkAccess( $id );

        //---

        $collection = $this->getCollection( 'user' );

        $user = $collection->findOne( [ '_id' => $id ] );

        //---

        // Ensure $data is an array.
        if( !is_array($data) ){ $data = array(); }

        // Protect these values from the client setting them manually.
        unset( $data['id'], $data['user'], $data['createdAt'], $data['updatedAt'] );

        //---

        $new = false;

        if( is_null($user) ){

            $user = [
                'id'        => $id,
                'createdAt' => new DateTime(),
                'updatedAt' => new DateTime(),
            ];

            $new = true;

        } else {
            $user = [ 'id' => $user['_id'] ] + $user;
        }

        //---

        $data = array_merge( $user, $data );

        //---

        $user = new User( $data );

        //-----------------------------------------

        $validation = $user->validate();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //-----------------------------------------

        if ($new){

            $collection->insert( $user->toMongoArray() );

        } else {

            $lastUpdated = new \MongoDate($user->updatedAt->getTimestamp(), (int)$user->updatedAt->format('u'));

            // Record the time we updated the user.
            $user->updatedAt = new DateTime();

            // updatedAt is included in the query so that data isn't overwritten
            // if the User has changed since this process loaded it.
            $result = $collection->update(
                ['_id' => $user->id, 'updatedAt' => $lastUpdated],
                $user->toMongoArray(),
                ['upsert' => false, 'multiple' => false]
            );

            // Ensure that one (and only one) document was updated.
            // If not, something when wrong.
            if ($result['nModified'] !== 1) {
                throw new \RuntimeException('Unable to update User. This might be because "updatedAt" has changed.');
            }

        } // if

        //------------------------

        $user = new Entity( $user );

        // Set the user in the AbstractResource so it can be used for route generation.
        $this->setRouteUser( $user );

        //---

        return $user;

    } // function

} // class
