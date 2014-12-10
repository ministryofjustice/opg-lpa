<?php
namespace Application\Model\Rest\Applications;

use RuntimeException;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\UserConsumerInterface;

use Zend\Paginator\Adapter\Null as PaginatorNull;
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
     * Create a new LAP.
     *
     * @param  mixed $data
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        $this->checkAccess();

        //------------------------

        $document = new Document\Document( $data );

        $validation = $document->validate();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
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

            $id = $csprng->GetInt(1, 99999999999);

            // Check if the id already exists. We're looking for a value of null.
            $exists = $collection->findOne( [ '_id'=>$id ], [ '_id'=>true ] );

        } while( !is_null($exists) );

        //----------------------------

        $lpa = new Lpa([
            'id'                => $id,
            'createdAt'         => new DateTime(),
            'updatedAt'         => new DateTime(),
            'user'              => $this->getRouteUser()->userId(),
            'locked'            => false,
            'whoAreYouAnswered' => false,
            'document'          => $document,
        ]);

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
        $result = $this->getCollection('lpa')->findOne( [ '_id'=>(int)$id, 'user'=>$this->getRouteUser()->userId() ] );

        if( is_null($result) ){
            return new ApiProblem( 404, 'Document not found' );
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

        // Get the collection...
        $cursor = $this->getCollection('lpa')->find( $query );

        $count = $cursor->count();

        // If there are no records, just return an empty paginator...
        if( $count == 0 ){
            return new Collection( new PaginatorNull );
        }

        //---

        // Map the results into a Zend Paginator, lazely converting them to LPA instances as we go...
        $collection = new Collection(new PaginatorCallback(
            function($offset, $itemCountPerPage) use ($cursor){
                // getItems callback

                $cursor->sort( [ 'createdAt' => -1 ] );
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
        ));

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

        $result['updatedAt'] = new \MongoDate();

        $collection->save( $result );

        return true;

    } // function


} // class
