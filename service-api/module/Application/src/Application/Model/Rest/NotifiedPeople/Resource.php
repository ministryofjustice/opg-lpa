<?php

namespace Application\Model\Rest\NotifiedPeople;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;
use Zend\Paginator\Adapter\NullFill as PaginatorNull;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'notified-people';

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier = 'resourceId';

    /**
     * Resource type
     *
     * @var string
     */
    protected $type = self::TYPE_COLLECTION;

    /**
     * Create a new Attorney.
     *
     * @param  mixed $data
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        //---

        $person = new NotifiedPerson( $data );

        /**
         * If the client has not passed an id, set it to max(current ids) + 1.
         * The array is seeded with 0, meaning if this is the first attorney the id will be 1.
         */
        if( is_null($person->id) ){

            $ids = array( 0 );
            foreach( $lpa->document->peopleToNotify as $a ){ $ids[] = $a->id; }
            $person->id = (int)max( $ids ) + 1;

        } // if

        //---

        $validation = $person->validateForApi();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        $lpa->document->peopleToNotify[] = $person;

        $this->updateLpa( $lpa );

        return new Entity( $person, $lpa );

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

        //---

        $lpa = $this->getLpa();

        foreach( $lpa->document->peopleToNotify as $person ){
            if( $person->id == (int)$id ){
                return new Entity( $person, $lpa );
            }
        }

        return new ApiProblem( 404, 'Document not found' );

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
        $document = $lpa->document;

        foreach( $document->peopleToNotify as $key=>$person ) {

            if ($person->id == (int)$id) {

                $person = new NotifiedPerson( $data );

                $person->id = (int)$id;

                //---

                $validation = $person->validateForApi();

                if( $validation->hasErrors() ){
                    return new ValidationApiProblem( $validation );
                }

                //---

                $document->peopleToNotify[$key] = $person;

                $this->updateLpa( $lpa );

                return new Entity( $person, $lpa );

            } // if

        } // foreach

        return new ApiProblem( 404, 'Document not found' );

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

        //---

        $lpa = $this->getLpa();
        $document = $lpa->document;

        foreach( $document->peopleToNotify as $key=>$person ){

            if( $person->id == (int)$id ){

                // Remove the entry...
                unset( $document->peopleToNotify[$key] );

                //---

                $this->updateLpa( $lpa );

                return true;

            } // if

        } // foreach

        return new ApiProblem( 404, 'Document not found' );

    } // function

} // class
