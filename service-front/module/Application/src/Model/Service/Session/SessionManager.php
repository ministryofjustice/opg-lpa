<?php
namespace Application\Model\Service\Session;

use Zend\Session\Container;
use Zend\Session\SessionManager as ZFSessionManager;

class SessionManager extends ZFSessionManager {

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
