<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\DeleteAccountConfirmHandler;
use Application\Handler\Factory\DeleteAccountConfirmHandlerFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DeleteAccountConfirmHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $authenticationService = $this->createMock(AuthenticationService::class);
        $userService = $this->createMock(UserService::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $renderer],
                [AuthenticationService::class, $authenticationService],
                [UserService::class, $userService],
            ]);

        $factory = new DeleteAccountConfirmHandlerFactory();
        $handler = $factory($container, DeleteAccountConfirmHandler::class);

        $this->assertInstanceOf(DeleteAccountConfirmHandler::class, $handler);
    }
}
