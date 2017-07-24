<?php
namespace Application\Model\Rest\Applications;

use MongoDB\BSON\UTCDateTime;
use RuntimeException;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\UserConsumerInterface;

use Zend\Paginator\Adapter\NullFill as PaginatorNull;
use Zend\Paginator\Adapter\Callback as PaginatorCallback;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document;

use Application\Library\DateTime;
use Application\Library\Random\Csprng;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

/**
 * Application Resource
 *
 * Class Resource
 * @package Application\Model\Rest\Applications
 */
class Resource extends AbstractResource implements UserConsumerInterface {

    public function getName(){ return 'applications'; }
    public function getIdentifier(){ return 'lpaId'; }

    public function getType(){
        return self::TYPE_COLLECTION;
    }


    /**
     * Filters out all top level keys that the user cannot directly set.
     *
     * @param array $data
     * @return mixed
     */
    private function filterIncomingData( array $data ){

        return array_intersect_key( $data, array_flip([
            'document',
            'metadata',
            'payment',
            'repeatCaseNumber'
        ]));

    }

    //-------------------------------------------

    /**
     * Create a new LPA.
     *
     * @param  mixed $data
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        $this->checkAccess();

        //------------------------

        // If no data was passed, represent with an empty array.
        if( is_null($data) ){
            $data = array();
        }

        //----------------------------
        // Generate an id for the LPA

        $collection = $this->getCollection('lpa');

        $csprng = new Csprng();

        /*
         * Generate a random 11-digit number to use as the LPA id.
         * This loops until we find one that's 'free'.
         */
        do {

            $id = $csprng->GetInt(1000000, 99999999999);

            // Check if the id already exists. We're looking for a value of null.
            $exists = $collection->findOne( [ '_id'=>$id ], [ '_id'=>true ] );

        } while( !is_null($exists) );

        //----------------------------

        $lpa = new Lpa([
            'id'                => $id,
            'startedAt'         => new DateTime(),
            'updatedAt'         => new DateTime(),
            'user'              => $this->getRouteUser()->userId(),
            'locked'            => false,
            'whoAreYouAnswered' => false,
            'document'          => new Document\Document(),
        ]);

        //---

        $data = $this->filterIncomingData( $data );

        if( !empty($data) ){
            $lpa->populate( $data );
        }

        //---

        if( $lpa->validate()->hasErrors() ){

            /*
             * This is not based on user input (we already validated the Document above),
             * thus if we have errors here it is our fault!
             */
            throw new RuntimeException('A malformed LPA object was created');

        }

        $collection->insert( $lpa->toMongoArray() );

        $entity = new Entity( $lpa );

        return $entity;

    } // function


    public function patch($data, $id){

        $this->checkAccess();

        //------------------------

        $lpa = $this->fetch( $id )->getLpa();

        //---

        $data = $this->filterIncomingData( $data );

        if( !empty($data) ){
            $lpa->populate( $data );
        }

        //---

        $validation = $lpa->validate();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        $this->updateLpa( $lpa );

        return new Entity( $lpa );

    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch($id){

        $this->checkAccess();

        //------------------------

        // Note: user has to match.
        $userId = $this->getRouteUser()->userId();
        $result = $this->getCollection('lpa')->findOne( [ '_id'=>(int)$id, 'user'=> $userId] );

        if( is_null($result) ){
            return new ApiProblem( 
                404, 
                'Document ' . $id . ' not found for user ' . $this->getRouteUser()->userId() 
            );
        }

        $result = [ 'id' => $result['_id'] ] + $result;

        $lpa = new Lpa( $result );

        $entity = new Entity( $lpa );

        return $entity;

    } // function


    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return Collection
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetchAll($params = array()){

        $this->checkAccess();

        //------------------------

        $query = [ 'user'=>$this->getRouteUser()->userId() ];

        // Merge in any filter requirements...
        if( isset($params['filter']) && is_array($params['filter']) ){
            $query = array_merge( $params, $query );
        }

        //---

        // If we have a search query...
        if( isset($params['search']) && strlen(trim($params['search'])) > 0 ) {

            $search = trim($params['search']);

            // If the string is numeric, assume it's an LPA id.
            if( is_numeric($search) ) {

                $query['_id'] = (int)$search;

            } else {

                // If it starts with an A and everything that follows after is numeric...
                if( substr(strtoupper($search),0,1) == 'A' && is_numeric( $ident = preg_replace('/\s+/', '', substr($search, 1)) ) ) {

                    // Assume it's an LPA id.
                    $query['_id'] = (int)$ident;

                } else {

                    // Otherwise assume it's a name.
                    $query[ '$text' ] = [ '$search' => '"'.trim($params['search']).'"' ];

                } // if

            } // if

        } // if search

        //---

        // Get the collection...
        $cursor = $this->getCollection('lpa')->find( $query );

        $count = $cursor->count();

        // If there are no records, just return an empty paginator...
        if( $count == 0 ){
            return new Collection( new PaginatorNull, $this->getRouteUser()->userId() );
        }

        //---

        // Map the results into a Zend Paginator, lazely converting them to LPA instances as we go...
        $callback = new PaginatorCallback(
            function($offset, $itemCountPerPage) use ($cursor){
                // getItems callback

                $cursor->sort( [ 'updatedAt' => -1 ] );
                $cursor->skip( $offset );
                $cursor->limit( $itemCountPerPage );

                // Convert the results to instances of the LPA object..
                $items = array_map( function($lpa){
                    $lpa = [ 'id' => $lpa['_id'] ] + $lpa;
                    return new Lpa( $lpa );
                }, iterator_to_array( $cursor ) );

                return $items;
            },
            function() use ($count){
                // count callback
                return $count;
            }
        );

        $collection = new Collection( $callback, $this->getRouteUser()->userId() );

        //---

        return $collection;

    } // function


    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|bool
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function delete($id){

        $this->checkAccess();

        //------------------------

        $collection = $this->getCollection('lpa');

        $result = $collection->findOne( [ '_id'=>(int)$id, 'user'=>$this->getRouteUser()->userId() ], [ '_id'=>true ] );

        if( is_null($result) ){
            return new ApiProblem( 404, 'Document not found' );
        }

        //---

        /*
         * We don't want to remove the document entirely as we need to make sure the same ID isn't reassigned.
         * So we just strip the document down to '_id' and 'updatedAt'.
         */

        $result['updatedAt'] = new UTCDateTime();

        $collection->save( $result );

        return true;

    } // function

    /**
     * Deletes all applications for the current user.
     *
     * @return bool
     */
    public function deleteAll(){

        $this->checkAccess();

        //------------------------

        $query = [ 'user'=>$this->getRouteUser()->userId() ];

        $lpas = $this->getCollection('lpa')->find( $query, [ '_id' => true ] );

        foreach( $lpas as $lpa ){
            $this->delete( $lpa['_id'] );
        }

        return true;

    } // function

} // class
