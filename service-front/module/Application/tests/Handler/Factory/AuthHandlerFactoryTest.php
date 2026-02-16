<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\DeletedAccountHandler;
use Application\Handler\Factory\DeletedAccountHandlerFactory;
use Application\Handler\Factory\LoginHandlerFactory;
use Application\Handler\Factory\LogoutHandlerFactory;
use Application\Handler\Factory\SessionExpiryHandlerFactory;
use Application\Handler\LoginHandler;
use Application\Handler\LogoutHandler;
use Application\Handler\SessionExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Session\SessionManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AuthHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testLoginHandlerFactoryReturnsHandler(): void
    {
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $sessionManagerSupport->method('getSessionManager')->willReturn($sessionManager);

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($sessionManagerSupport) {
                return match ($service) {
                    TemplateRendererInterface::class => $this->createMock(TemplateRendererInterface::class),
                    FormElementManager::class => $this->createMock(FormElementManager::class),
                    AuthenticationService::class => $this->createMock(AuthenticationService::class),
                    SessionManagerSupport::class => $sessionManagerSupport,
                    SessionUtility::class => $this->createMock(SessionUtility::class),
                    LpaApplicationService::class => $this->createMock(LpaApplicationService::class),
                    FlashMessenger::class => $this->createMock(FlashMessenger::class),
                    'config' => [],
                    default => null,
                };
            });

        $factory = new LoginHandlerFactory();
        $handler = $factory($this->container);

        $this->assertInstanceOf(LoginHandler::class, $handler);
    }

    public function testSessionExpiryHandlerFactoryReturnsHandler(): void
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
                    default => null,
                };
            });

        $factory = new SessionExpiryHandlerFactory();
        $handler = $factory($this->container);

        $this->assertInstanceOf(SessionExpiryHandler::class, $handler);
    }

    public function testLogoutHandlerFactoryReturnsHandler(): void
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
                    default => null,
                };
            });

        $factory = new LogoutHandlerFactory();
        $handler = $factory($this->container);

        $this->assertInstanceOf(LogoutHandler::class, $handler);
    }

    public function testDeletedAccountHandlerFactoryReturnsHandler(): void
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
                    default => null,
                };
            });

        $factory = new DeletedAccountHandlerFactory();
        $handler = $factory($this->container);

        $this->assertInstanceOf(DeletedAccountHandler::class, $handler);
    }
}
