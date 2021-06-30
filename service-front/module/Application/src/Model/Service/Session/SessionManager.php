<?php
namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends LaminasSessionManager
{
    /**
     * @var Container $container
     */
    private $container;

    public function __construct(Container $container = null)
    {
        // parent constructor has to be called first, in case we have to
        // make a container within this constructor
        parent::__construct();

        if (is_null($container)) {
            $container = new Container('initialised', $this);
        }

        $this->container = $container;
    }

    /**
     * Tracks whether we've seen this session before and does a regenerateId() if not.
     *
     * @return void
     */
    public function initialise(): void
    {
        // If it's a new session, regenerate the id.
        if (!isset($this->container->init)) {
            $this->regenerateId(TRUE);
            $this->container->init = TRUE;
        }
    }
}
