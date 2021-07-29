<?php
namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends LaminasSessionManager
{
    /**
     * @var Container $container
     */
    private $container;

    public function __construct(SaveHandlerInterface $saveHandler, Container $container = null)
    {
        // This has to be done in the SessionManager constructor. If left
        // until after this, setSaveHandler() appears to have no effect,
        // as the code below which constructs the container causes the
        // session to be initialised with an incorrect save handler.
        //$this->setSaveHandler($saveHandler);

        // parent constructor has to be called first, in case we have to
        // make a container within this constructor
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
            $this->regenerateId(TRUE);
            $this->container->init = TRUE;
        }
    }
}
