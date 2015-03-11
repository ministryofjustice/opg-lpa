<?php
namespace Application\Model\Rest\Status;

use Application\Model\Rest\AbstractResource;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

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

        die('In progress.');

        //---

        return new Entity( $lpa );

    }

} // class
