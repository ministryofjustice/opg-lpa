<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\DeleteAccountHandler;
use Application\Handler\Factory\DeleteAccountHandlerFactory;
use Application\Model\Service\Authentication\AuthenticationService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DeleteAccountHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $authenticationService = $this->createMock(AuthenticationService::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $renderer],
                [AuthenticationService::class, $authenticationService],
            ]);

        $factory = new DeleteAccountHandlerFactory();
        $handler = $factory($container, DeleteAccountHandler::class);

        $this->assertInstanceOf(DeleteAccountHandler::class, $handler);
    }
}
