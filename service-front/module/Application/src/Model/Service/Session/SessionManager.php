<?php
namespace Application\Model\Service\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager as LaminasSessionManager;

class SessionManager extends LaminasSessionManager {

    /**
     * @var string $lastMatchedRouteName
     */
    private $lastMatchedRouteName;

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
    public function getLastMatchedRouteName(): string
    {
        return $this->lastMatchedRouteName;
    }

    /**
     * @param string $matchedRouteName
     */
    public function setLastMatchedRouteName(string $matchedRouteName): void
    {
        if (!isset($this->lastMatchedRouteName) || $this->lastMatchedRouteName !== $matchedRouteName) {
            $this->lastMatchedRouteName = $matchedRouteName;
        }
    }
} // class
