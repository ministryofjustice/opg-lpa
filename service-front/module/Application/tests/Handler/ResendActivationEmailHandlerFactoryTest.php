<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\Factory\ResendActivationEmailHandlerFactory;
use Application\Handler\ResendActivationEmailHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ResendActivationEmailHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $formElementManager = $this->createMock(FormElementManager::class);
        $userService = $this->createMock(UserService::class);

        $container
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function ($service) use ($renderer, $formElementManager, $userService) {
                return match ($service) {
                    TemplateRendererInterface::class => $renderer,
                    FormElementManager::class => $formElementManager,
                    UserService::class => $userService,
                    default => null,
                };
            });

        $factory = new ResendActivationEmailHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(ResendActivationEmailHandler::class, $handler);
    }
}
