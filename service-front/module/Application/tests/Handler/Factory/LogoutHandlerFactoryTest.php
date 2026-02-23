<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\LogoutHandlerFactory;
use Application\Handler\LogoutHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LogoutHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private LogoutHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new LogoutHandlerFactory();
    }

    public function testFactoryReturnsLogoutHandler(): void
    {
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $sessionManagerSupport->method('getSessionManager')->willReturn($sessionManager);

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($sessionManagerSupport) {
                return match ($service) {
                    AuthenticationService::class => $this->createMock(AuthenticationService::class),
                    SessionManagerSupport::class => $sessionManagerSupport,
                    'config' => ['redirects' => ['logout' => '/']],
                };
            });

        $handler = ($this->factory)($this->container);

        $this->assertInstanceOf(LogoutHandler::class, $handler);
    }
}
