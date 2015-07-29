<?php
namespace Application\Model\Rest\RepeatCaseNumber;

use RuntimeException;

use Application\Model\Rest\AbstractResource;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'lpaId'; }
    public function getName(){ return 'repeat-case-number'; }

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

        return new Entity( $lpa->repeatCaseNumber, $lpa );

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

        $lpa->repeatCaseNumber = (isset($data['repeatCaseNumber'])) ? $data['repeatCaseNumber'] : null;

        if( !is_int($lpa->repeatCaseNumber) && is_numeric($lpa->repeatCaseNumber) ){
            $lpa->repeatCaseNumber = (int)$lpa->repeatCaseNumber;
        }

        //---

        $validation = $lpa->validateForApi();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        $this->updateLpa( $lpa );

        return new Entity( $lpa->repeatCaseNumber, $lpa );

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

        $lpa->repeatCaseNumber = null;

        //---

        $validation = $lpa->validateForApi();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        $this->updateLpa( $lpa );

        return true;

    } // function

} // class
