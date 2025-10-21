<?php

namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends LaminasSessionManager
{
    /** @var Container $container */
    private $container;

    /**
     * @param SaveHandlerInterface $saveHandler If defaults to null, the SessionManager is
     * constructed with the default save handler
     */
    public function __construct(?Container $container = null, ?SaveHandlerInterface $saveHandler = null)
    {
        // parent constructor has to be called first, in case we have to
        // make a container within this constructor;
        // for constructor signature, see:
        // https://docs.laminas.dev/laminas-session/manager/
        parent::__construct(null, null, $saveHandler);

        if (is_null($container)) {
            $container = new Container('initialised', $this);
        }

        $this->container = $container;
    }

    /**
     * Tracks whether we've seen this session before and does a regenerateId() if not.
     */
    public function initialise()
    {
        // If it's a new session, regenerate the id.
        if (!isset($this->container->init)) {
            $this->regenerateId(true);
            $this->container->init = true;
        }
    }
}
