<?php

namespace Application\Model\Rest\Users;

use Application\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\Rest\AbstractResource;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\User\User;

class Resource extends AbstractResource
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'users';

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier = 'userId';

    /**
     * Resource type
     *
     * @var string
     */
    protected $type = self::TYPE_COLLECTION;

    /**
     * Fetch the user. If the user does not exist, create them.
     *
     * @param  mixed $id
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch($id){

        $this->checkAccess( $id );

        //------------------------

        $user = $this->getCollection( 'user' )->findOne( [ '_id' => $id ] );

        //------------------------

        // If the user doesn't exist, we create it.
        // (the ID has already been validated with the authentication service)
        if( !is_array($user) ){

            // Create a new user...
            $user = $this->save( $id );

        } else {
            $user = [ 'id' => $id ] + $user;
            $user = new User( $user );
        }

        // The authentication service is the authoritative email address provider
        $user->email = [ 'address'=>$this->getAuthorizationService()->getIdentity()->email() ];

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

        $user = $this->save( $id, $data );

        // If it's not a user, it's a different kind of response, so return it.
        if( !( $user instanceof User ) ){
            return $user;
        }

        //---

        $user = new Entity( $user );

        // Set the user in the AbstractResource so it can be used for route generation.
        $this->setRouteUser( $user );

        //---

        return $user;

    } // function

    /**
     * Deletes the user AND all the user's LPAs!!!
     *
     * @param  mixed $id
     * @return ApiProblem|bool
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function delete($id){

        $this->checkAccess( $id );

        //------------------------

        // Delete all applications for the user.
        $this->getServiceLocator()->get('resource-applications')->deleteAll();

        // Delete the user's About Me details.
        $this->getCollection( 'user' )->deleteOne( [ '_id' => $id ] );

        return true;

    } // function

    //-----------------------------------------

    /**
     * Save the user to the database (or creates them if they don't exist)
     *
     * @param $id
     * @param $data
     * @return ValidationApiProblem|array|null|User
     */
    private function save( $id, $data = null ){

        $this->checkAccess( $id );

        //---

        $collection = $this->getCollection( 'user' );

        $user = $collection->findOne( [ '_id' => $id ] );

        //---

        // Ensure $data is an array.
        if( !is_array($data) ){ $data = array(); }

        // Protect these values from the client setting them manually.
        unset( $data['id'], $data['email'], $data['createdAt'], $data['updatedAt'] );

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

        // Keep email up to date with what's in the authentication service.
        $user->email = [ 'address'=>$this->getAuthorizationService()->getIdentity()->email() ];

        //-----------------------------------------

        if ($new){

            $collection->insertOne( $user->toArray(new DateCallback()) );

        } else {

            $validation = $user->validate();

            if( $validation->hasErrors() ){
                return new ValidationApiProblem( $validation );
            }

            $lastUpdated = new UTCDateTime($user->updatedAt);

            // Record the time we updated the user.
            $user->updatedAt = new DateTime();

            // updatedAt is included in the query so that data isn't overwritten
            // if the User has changed since this process loaded it.
            $result = $collection->updateOne(
                ['_id' => $user->id, 'updatedAt' => $lastUpdated],
                ['$set' => $user->toArray(new DateCallback())],
                ['upsert' => false, 'multiple' => false]
            );

            // Ensure that one (and only one) document was updated.
            // If not, something when wrong.
            if ($result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1) {
                throw new \RuntimeException('Unable to update User. This might be because "updatedAt" has changed.');
            }

        } // if

        //------------------------

        return $user;

    } // function

} // class
