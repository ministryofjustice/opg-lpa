<?php

namespace Application\Model\Rest\Status;

use Application\Library\Lpa\StateChecker;
use Application\Model\Rest\AbstractOLDResource;
use Application\Model\Rest\LpaConsumerInterface;

class Resource extends AbstractOLDResource implements LpaConsumerInterface
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'status';

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier = 'resourceId';

    /**
     * Resource type
     *
     * @var string
     */
    protected $type = self::TYPE_SINGULAR;

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
