<?php
namespace Application\Model\Rest\Seed;

use RuntimeException;

use Application\Model\Rest\Applications\Entity as ApplicationEntity;

use Application\Model\Rest\AbstractResource;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'lpaId'; }
    public function getName(){ return 'seed'; }

    public function getType(){
        return self::TYPE_SINGULAR;
    }

    /**
     * Fetch a resource
     *
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch(){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        //---

        if( !is_int( $lpa->seed ) ){
            return new Entity( null, $lpa );
        }

        //---

        $resource = $this->getServiceLocator()->get( 'resource-applications' );

        $lpaEntity  = $resource->fetch( $lpa->seed );

        if( !($lpaEntity instanceof ApplicationEntity) ){
            return new ApiProblem( 404, 'Invalid LPA identifier to seed from' );
        }

        //---

        $seedLpa = $lpaEntity->getLpa();

        // Should need to check this, but just to be safe...
        if( $seedLpa->user != $lpa->user ){
            return new ApiProblem( 400, 'Invalid LPA identifier to seed from' );
        }

        //---

        return new Entity( $seedLpa, $lpa );

    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|Entity
     */
    public function update($data, $id){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        if( !isset($data['seed']) || !is_numeric($data['seed']) ){
            return new ApiProblem( 400, 'Invalid LPA identifier to seed from' );
        }

        //---

        $resource = $this->getServiceLocator()->get( 'resource-applications' );

        $lpaEntity  = $resource->fetch( $data['seed'] );

        if( !($lpaEntity instanceof ApplicationEntity) ){
            return new ApiProblem( 400, 'Invalid LPA identifier to seed from' );
        }

        //---

        $seedLpa = $lpaEntity->getLpa();

        // Should need to check this, but just to be safe...
        if( $seedLpa->user != $lpa->user ){
            return new ApiProblem( 400, 'Invalid LPA identifier to seed from' );
        }

        //---

        $lpa->seed = $seedLpa->id;

        //---

        if( $lpa->validate()->hasErrors() ){

            /*
             * This is not based on user input (we already validated the Document above),
             * thus if we have errors here it is our fault!
             */
            throw new RuntimeException('A malformed LPA object');

        }

        $this->updateLpa( $lpa );

        return new Entity( $seedLpa, $lpa );

    } // function

    /**
     * Delete a resource
     *
     * @return ApiProblem|bool
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function delete(){

        $this->checkAccess();

        //------------------------

        $lpa = $this->getLpa();

        $lpa->seed = null;

        //---

        if( $lpa->validate()->hasErrors() ){

            /*
             * This is not based on user input (we already validated the Document above),
             * thus if we have errors here it is our fault!
             */
            throw new RuntimeException('A malformed LPA object');

        }

        $this->updateLpa( $lpa );

        return true;

    } // function

} // class
