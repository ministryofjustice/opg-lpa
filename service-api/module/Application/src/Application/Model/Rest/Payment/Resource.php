<?php

namespace Application\Model\Rest\Payment;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractOLDResource;
use Application\Model\Rest\LpaConsumerInterface;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use RuntimeException;

class Resource extends AbstractOLDResource implements LpaConsumerInterface
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'payment';

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

        $lpa->payment = new Payment( $data );

        //---

        $validation = $lpa->payment->validate();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        if( $lpa->validate()->hasErrors() ){
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
            throw new RuntimeException('A malformed LPA object');

        }



        $this->updateLpa( $lpa );

        return true;

    } // function

} // class
