<?php

namespace Application\Model\Rest\WhoAreYou;

use Application\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use RuntimeException;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'who-are-you';

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
     * Fetch a resource
     *
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch(){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        // Return false if the value is not set.
        $result = ( is_null($lpa->whoAreYouAnswered) )? false: $lpa->whoAreYouAnswered;

        return new Entity( $result, $lpa );

    }

    /**
     * Adds a Who Are You answer.
     *
     * @param  mixed $data
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        if( $lpa->whoAreYouAnswered === true ){
            return new ApiProblem( 403, 'Question already answered' );
        }

        //---

        $answer = new WhoAreYou($data);

        $validation = $answer->validate();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        $lpa->whoAreYouAnswered = true;

        //---

        if( $lpa->validate()->hasErrors() ){

            /*
             * This is not based on user input (we already validated the Document above),
             * thus if we have errors here it is our fault!
             */
            throw new RuntimeException('A malformed LPA object was created');

        }

        //---

        // We update the LPA first as there's a chance a RuntimeException will be thrown
        // if there's an 'updatedAt' mismatch.

        $this->updateLpa( $lpa );

        //---

        $this->collection->insertOne( $answer->toArray(new DateCallback()) );

        //---

        return new Entity( $lpa->whoAreYouAnswered, $lpa );

    } // function

} // class
