<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Application\Service\Factory\SessionManagerSupportFactory;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SessionManagerSupportFactoryTest extends TestCase
{
    public function testFactoryReturnsSessionManagerSupport(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'SessionManager'      => $this->createMock(SessionManager::class),
            SessionUtility::class => $this->createMock(SessionUtility::class),
        });

        $support = (new SessionManagerSupportFactory())($container);

        $this->assertInstanceOf(SessionManagerSupport::class, $support);
    }
}
