<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\AboutYouHandler;
use Application\Handler\Factory\AboutYouHandlerFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AboutYouHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private AboutYouHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new AboutYouHandlerFactory();
    }

    public function testFactoryReturnsAboutYouHandler(): void
    {
        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) {
                return match ($service) {
                    TemplateRendererInterface::class => $this->createMock(TemplateRendererInterface::class),
                    FormElementManager::class => $this->createMock(FormElementManager::class),
                    AuthenticationService::class => $this->createMock(AuthenticationService::class),
                    UserService::class => $this->createMock(UserService::class),
                    SessionUtility::class => $this->createMock(SessionUtility::class),
                    FlashMessenger::class => $this->createMock(FlashMessenger::class),
                };
            });

        $handler = ($this->factory)($this->container);

        $this->assertInstanceOf(AboutYouHandler::class, $handler);
    }
}
