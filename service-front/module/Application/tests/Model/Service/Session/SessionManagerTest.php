<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Laminas\Session\SessionManager;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Session\Container;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class SessionManagerTest extends MockeryTestCase
{
    /**
     * (@runInSeparateProcess annotation is required so that session handling is
     * managed correctly; without it, the main phpunit process has effectively
     * started a session, and starting a session manager here will result in a
     * "session_regenerate_id(): Cannot regenerate session id - session is not active"
     * error)
     */
    #[RunInSeparateProcess]
    public function testSessionManager(): void
    {
        $sessionManager = new SessionManager();
        $sessionManager->start();

        $origId = $sessionManager->getId();

        $supportManagerSupport = new SessionManagerSupport($sessionManager);
        $supportManagerSupport->initialise();

        $container = new Container('initialised');


        $this->assertEquals($container->init, true);
        $this->assertNotSame($origId, $sessionManager->getId());
    }
}
