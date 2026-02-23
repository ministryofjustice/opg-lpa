<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\Factory\RegisterHandlerFactory;
use Application\Handler\RegisterHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class RegisterHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $formElementManager = $this->createMock(FormElementManager::class);
        $userService = $this->createMock(UserService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnCallback(function ($service) use ($renderer, $formElementManager, $userService, $logger) {
                return match ($service) {
                    TemplateRendererInterface::class => $renderer,
                    FormElementManager::class => $formElementManager,
                    UserService::class => $userService,
                    LoggerInterface::class => $logger,
                    default => null,
                };
            });

        $factory = new RegisterHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(RegisterHandler::class, $handler);
    }
}
