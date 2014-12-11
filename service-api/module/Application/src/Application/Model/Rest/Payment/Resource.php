<?php
namespace Application\Model\Rest\Payment;

use RuntimeException;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

use Application\Model\Rest\AbstractResource;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'lpaId'; }
    public function getName(){ return 'payment'; }

    public function getType(){
        return self::TYPE_SINGULAR;
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch(){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        return new Entity( $lpa->payment, $lpa );

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


        $lpa->payment = (isset($data['payment'])) ? new Payment($data['payment']) : null;

        //---

        $validation = $lpa->payment->validate();

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

        return new Entity( $lpa->payment, $lpa );

    } // function

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|bool
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function delete(){

        $this->checkAccess();

        //------------------------

        $lpa = $this->getLpa();

        $lpa->payment = null;

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
