<?php
namespace Application\Model\Rest\Applications;

use RuntimeException;

use Application\Model\Rest\AbstractResource;

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
class Resource extends AbstractResource {

    public function getName(){ return 'applications'; }
    public function getIdentifier(){ return 'lpaId'; }

    /**
     * Create a new LAP.
     *
     * @param  mixed $data
     * @return Entity|ApiProblem
     * @ throw UnauthorizedException If the current user is not authorized.
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
            'user'              => $this->getRouteUser(),
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

        # TODO - Bring this back in!
        #$result = $collection->insert( $lpa->toMongoArray() );

        $entity = new Entity( $lpa );

        return $entity;

    } // function


    public function fetch($id){

        $this->checkAccess();

        //------------------------

        // Note: user has to match.
        $result = $this->getCollection('lpa')->findOne( [ '_id'=>(int)$id, 'user'=>$this->getRouteUser() ] );

        if( is_null($result) ){
            return new ApiProblem( 404, 'Document not found' );
        }

        $result = [ 'id' => $result['_id'] ] + $result;

        $lpa = new Lpa( $result );

        $entity = new Entity( $lpa );

        return $entity;

    } // function

} // class
