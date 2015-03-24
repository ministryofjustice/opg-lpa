<?php
namespace Application\Model\Rest\Status;

use Application\Model\Rest\AbstractResource;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\Lpa\StateChecker;

/**
 * Status Resource
 *
 * Class Resource
 * @package Application\Model\Rest\Status
 */
class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    public function getIdentifier(){ return 'resourceId'; }
    public function getName(){ return 'status'; }

    public function getType(){
        return self::TYPE_SINGULAR;
    }

    /**
     * Fetch a resource
     *
     * @return Entity
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch(){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        //---

        $state = new StateChecker( $lpa );

        $result = array(
            'started' => $state->isStateStarted(),
            'created' => $state->isStateCreated(),
            'completed' => $state->isStateCompleted(),
        );

        //---

        return new Entity( $result, $lpa );

    }

} // class
