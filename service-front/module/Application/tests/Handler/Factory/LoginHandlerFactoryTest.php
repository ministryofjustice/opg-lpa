<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\LoginHandlerFactory;
use Application\Handler\LoginHandler;
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

class LoginHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private LoginHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new LoginHandlerFactory();
    }

    public function testFactoryReturnsLoginHandler(): void
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
                };
            });

        $handler = ($this->factory)($this->container);

        $this->assertInstanceOf(LoginHandler::class, $handler);
    }
}
