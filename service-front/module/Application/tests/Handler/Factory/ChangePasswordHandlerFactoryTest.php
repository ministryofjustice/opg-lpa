<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\ChangePasswordHandler;
use Application\Handler\Factory\ChangePasswordHandlerFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ChangePasswordHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $formElementManager = $this->createMock(FormElementManager::class);
        $authenticationService = $this->createMock(AuthenticationService::class);
        $userService = $this->createMock(UserService::class);
        $flashMessenger = $this->createMock(FlashMessenger::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $renderer],
                [FormElementManager::class, $formElementManager],
                [AuthenticationService::class, $authenticationService],
                [UserService::class, $userService],
                [FlashMessenger::class, $flashMessenger],
            ]);

        $factory = new ChangePasswordHandlerFactory();
        $handler = $factory($container, ChangePasswordHandler::class);

        $this->assertInstanceOf(ChangePasswordHandler::class, $handler);
    }
}
