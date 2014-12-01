<?php
namespace Application\Model\Rest\Applications;

use Application\Model\Rest\AbstractResource;

use ZfcRbac\Exception\UnauthorizedException;

/**
 * Application Resource
 *
 * Class Resource
 * @package Application\Model\Rest\Applications
 */
class Resource extends AbstractResource {

    /**
     * Create a new LAP.
     *
     * @param  mixed $data
     * @return Entity|Error
     * @ throw UnauthorizedException If the current user is not authorized.
     */
    public function create($data){

        if (!$this->getAuthorizationService()->isGranted('create-lpa')) {
            throw new UnauthorizedException('You are not allowed !');
        }

        die('creating!');

    } // class

} // class
