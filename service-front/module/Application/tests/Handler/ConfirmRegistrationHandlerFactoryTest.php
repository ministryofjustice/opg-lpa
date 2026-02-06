<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\ConfirmRegistrationHandler;
use Application\Handler\Factory\ConfirmRegistrationHandlerFactory;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use Laminas\Authentication\AuthenticationService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ConfirmRegistrationHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $userService = $this->createMock(UserService::class);
        $authenticationService = $this->createMock(AuthenticationService::class);
        $sessionManagerSupport = $this->createMock(SessionManagerSupport::class);

        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnCallback(function ($service) use ($renderer, $userService, $authenticationService, $sessionManagerSupport) {
                return match ($service) {
                    TemplateRendererInterface::class => $renderer,
                    UserService::class => $userService,
                    AuthenticationService::class => $authenticationService,
                    SessionManagerSupport::class => $sessionManagerSupport,
                    default => null,
                };
            });

        $factory = new ConfirmRegistrationHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(ConfirmRegistrationHandler::class, $handler);
    }
}
