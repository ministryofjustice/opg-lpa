<?php
namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\SessionManager;
use ApplicationTest\Model\Service\ServiceTestHelper;
use Laminas\Session\Container;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class SessionManagerTest extends MockeryTestCase
{
    /**
     * (@runInSeparateProcess annotation is required so that session handling is
     * managed correctly; without it, the main phpunit process has effectively
     * started a session, and starting a session manager here will result in a
     * "session_regenerate_id(): Cannot regenerate session id - session is not active"
     * error)
     *
     * @runInSeparateProcess
     */
    public function testSessionManager() : void
    {
        $container = new Container('initialised');

        $sessionManager = new SessionManager($container);
        $sessionManager->start();

        $origId = $sessionManager->getId();

        $sessionManager->initialise();

        $this->assertEquals($container->init, TRUE);
        $this->assertNotSame($origId, $sessionManager->getId());
    }
}
