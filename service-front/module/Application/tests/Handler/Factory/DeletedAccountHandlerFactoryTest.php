<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\DeletedAccountHandler;
use Application\Handler\Factory\DeletedAccountHandlerFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Session\SessionManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DeletedAccountHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private DeletedAccountHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new DeletedAccountHandlerFactory();
    }

    public function testFactoryReturnsDeletedAccountHandler(): void
    {
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $sessionManagerSupport->method('getSessionManager')->willReturn($sessionManager);

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($sessionManagerSupport) {
                return match ($service) {
                    TemplateRendererInterface::class => $this->createMock(TemplateRendererInterface::class),
                    AuthenticationService::class => $this->createMock(AuthenticationService::class),
                    SessionManagerSupport::class => $sessionManagerSupport,
                };
            });

        $handler = ($this->factory)($this->container);

        $this->assertInstanceOf(DeletedAccountHandler::class, $handler);
    }
}
