<?php
namespace Application\Model\Rest\WhoAreYou;

use RuntimeException;

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
     * @param  mixed $id
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

            die('NO - you cannot set this value again!');
        }

        //---

        die('Add the restults into the stats collection.');

        //---

        $lpa->whoAreYouAnswered = true;

        $this->updateLpa( $lpa );

        return new Entity( $lpa->whoAreYouAnswered, $lpa );

    } // function

} // class
