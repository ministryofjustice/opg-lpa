<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\SessionKeepAliveHandlerFactory;
use Application\Handler\SessionKeepAliveHandler;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SessionKeepAliveHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $sessionManager = $this->createMock(SessionManager::class);

        $container->expects($this->once())
            ->method('get')
            ->with(SessionManager::class)
            ->willReturn($sessionManager);

        $factory = new SessionKeepAliveHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(SessionKeepAliveHandler::class, $handler);
    }
}
