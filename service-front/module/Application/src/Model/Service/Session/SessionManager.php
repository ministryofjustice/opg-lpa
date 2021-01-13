<?php
namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends LaminasSessionManager {

    /**
     * Tracks whether we've seen this session before and does a regenerateId() if not.
     */
    public function initialise(){

        $container = new Container( 'initialised', $this );

        // If it's a new session, regenerate the id.
        if (!isset($container->init)) {
            $this->regenerateId(true);
            $container->init = true;
        }

    }
} // class
