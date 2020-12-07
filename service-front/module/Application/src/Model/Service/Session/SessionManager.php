<?php
namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends LaminasSessionManager {

    /**
     * @var string $lastMatchedRoute
     */
    private $lastMatchedRoute;

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

    /**
     * @return string
     */
    public function getLastMatchedRoute(): string
    {
        return $this->lastMatchedRoute;
    }

    /**
     * @param string $matchedRoute
     */
    public function setLastMatchedRoute(string $matchedRoute): void
    {
        if (!isset($this->lastMatchedRoute) || $this->lastMatchedRoute !== $matchedRoute) {
            $this->lastMatchedRoute = $matchedRoute;
        }
    }

} // class
