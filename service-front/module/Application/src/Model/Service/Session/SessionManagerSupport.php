<?php

namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager;

final class SessionManagerSupport
{
    public function __construct(readonly private SessionManager $sessionManager)
    {
    }

    public function initialise(): void
    {


        $container = new Container('initialised', $this->sessionManager);

        if (!isset($container->init)) {
            $this->sessionManager->regenerateId(true);
            $container->init = true;
        }
    }

    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }
}
