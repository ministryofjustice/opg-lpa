<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\SessionSetExpiryHandlerFactory;
use Application\Handler\SessionSetExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SessionSetExpiryHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $authService = $this->createMock(AuthenticationService::class);

        $container->expects($this->once())
            ->method('get')
            ->with(AuthenticationService::class)
            ->willReturn($authService);

        $factory = new SessionSetExpiryHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(SessionSetExpiryHandler::class, $handler);
    }
}
