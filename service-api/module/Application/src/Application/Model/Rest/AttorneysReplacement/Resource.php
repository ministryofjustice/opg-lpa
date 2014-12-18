<?php
namespace Application\Model\Rest\AttorneysReplacement;

use RuntimeException;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys;

use Application\Model\Rest\AbstractResource;

use Zend\Paginator\Adapter\Null as PaginatorNull;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'resourceId'; }
    public function getName(){ return 'replacement-attorneys'; }

    public function getType(){
        return self::TYPE_COLLECTION;
    }

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

        switch($data['type']){
            case 'trust':
                $attorney = new Attorneys\TrustCorporation( $data );
                break;
            case 'human':
                $attorney = new Attorneys\Human( $data );
                break;
            default:
                // TODO - return a ValidationApiProblem?
                throw new RuntimeException('Invalid type passed');
        }

        /**
         * If the client has not passed an id, set it to max(current ids) + 1.
         * The array is seeded with 0, meaning if this is the first attorney the id will be 1.
         */
        if( is_null($attorney->id) ){

            $ids = array( 0 );
            foreach( $lpa->document->replacementAttorneys as $a ){ $ids[] = $a->id; }
            $attorney->id = (int)max( $ids ) + 1;

        } // if

        //---


        $lpa->document->replacementAttorneys[] = $attorney;

        $this->updateLpa( $lpa );

        return new Entity( $attorney, $lpa );

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

        foreach( $lpa->document->replacementAttorneys as $attorney ){
            if( $attorney->id == (int)$id ){
                return new Entity( $attorney, $lpa );
            }
        }

        return new ApiProblem( 404, 'Document not found' );

    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return Collection
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetchAll($params = array()){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        $count = count($lpa->document->replacementAttorneys);

        // If there are no records, just return an empty paginator...
        if( $count == 0 ){
            return new Collection( new PaginatorNull, $lpa );
        }

        //---

        $collection = new Collection( new PaginatorArrayAdapter( $lpa->document->replacementAttorneys ), $lpa );

        // Always return all attorneys on one page.
        $collection->setItemCountPerPage($count);

        //---

        return $collection;


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

        foreach( $document->replacementAttorneys as $key=>$attorney ) {

            if ($attorney->id == (int)$id) {

                switch($data['type']){
                    case 'trust':
                        $attorney = new Attorneys\TrustCorporation( $data );
                        break;
                    case 'human':
                        $attorney = new Attorneys\Human( $data );
                        break;
                    default:
                        // TODO - return a ValidationApiProblem?
                        throw new RuntimeException('Invalid type passed');
                }

                $attorney->id = (int)$id;

                $document->replacementAttorneys[$key] = $attorney;

                $this->updateLpa( $lpa );

                return new Entity( $attorney, $lpa );

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

        foreach( $document->replacementAttorneys as $key=>$attorney ){

            if( $attorney->id == (int)$id ){

                // Remove the entry...
                unset( $document->replacementAttorneys[$key] );

                //---

                $validation = $document->validate();

                if( $validation->hasErrors() ){
                    return new ValidationApiProblem( $validation );
                }

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

            } // if

        } // foreach

        return new ApiProblem( 404, 'Document not found' );

    } // function

} // class
