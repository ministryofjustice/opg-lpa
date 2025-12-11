<?php

namespace Application\Model\Service\Session;

use Laminas\Session\SessionManager;

class SessionManagerSupport
{
    public function __construct(
        readonly private SessionManager $sessionManager,
        private SessionUtility $sessionUtility
    ) {
    }

    public function initialise(): void
    {
        if (!$this->sessionUtility->hasInMvc('initialised', 'init')) {
            $this->sessionManager->regenerateId(true);
            $this->sessionUtility->setInMvc('initialised', 'init', true);
        }
    }

    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }
}
