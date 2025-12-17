<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\SessionUtility;
use Laminas\Session\SessionManager;
use Mockery;
use Application\Model\Service\Session\SessionManagerSupport;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class SessionManagerTest extends MockeryTestCase
{
    public function testSessionManager(): void
    {
        $sessionManager = new SessionManager();
        $sessionManager->start();

        $origId = $sessionManager->getId();

        $sessionUtility =  Mockery::mock(SessionUtility::class);
        $sessionUtility
            ->shouldReceive('hasInMvc')
            ->with('initialised', 'init')
            ->andReturn(false);

        $sessionUtility
            ->shouldReceive('setInMvc')
            ->with('initialised', 'init', true);

        $supportManagerSupport = new SessionManagerSupport($sessionManager, $sessionUtility);
        $supportManagerSupport->initialise();

        $this->assertNotSame($origId, $sessionManager->getId());
    }
}
