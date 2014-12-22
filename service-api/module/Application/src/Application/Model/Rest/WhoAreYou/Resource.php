<?php
namespace Application\Model\Rest\WhoAreYou;

use RuntimeException;

use Opg\Lpa\DataModel\WHoAreYou\WhoAreYou;

use Application\Model\Rest\AbstractResource;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'lpaId'; }
        public function getName(){ return 'who-are-you'; }

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

        // Return false if the value is not set.
        $result = ( is_null($lpa->whoAreYouAnswered) )? false: $lpa->whoAreYouAnswered;

        return new Entity( $result, $lpa );

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

        // We update the LPA first as there's a chance a RuntimeException will be thrown
        // if there's an 'updatedAt' mismatch.

        $lpa->whoAreYouAnswered = true;

        $this->updateLpa( $lpa );

        //---

        $collection = $this->getCollection('stats-who');

        $collection->insert( $answer->toMongoArray() );

        //---

        return new Entity( $lpa->whoAreYouAnswered, $lpa );

    } // function

} // class
