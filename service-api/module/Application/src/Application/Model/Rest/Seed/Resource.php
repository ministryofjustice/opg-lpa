<?php

namespace Application\Model\Rest\Seed;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Applications\Entity as ApplicationEntity;
use Application\Model\Rest\Applications\Resource as ApplicationResource;
use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;
use RuntimeException;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'seed';

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier = 'lpaId';

    /**
     * Resource type
     *
     * @var string
     */
    protected $type = self::TYPE_SINGULAR;

    /**
     * @var ApplicationResource
     */
    private $applicationsResource;

    /**
     * @param ApplicationResource $applicationsResource
     */
    public function setApplicationsResource(ApplicationResource $applicationsResource)
    {
        $this->applicationsResource = $applicationsResource;
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

        $lpaEntity  = $this->applicationsResource->fetch( $lpa->seed );

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

        $lpaEntity  = $this->applicationsResource->fetch( $data['seed'] );

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
