<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Form\Error\FormLinkedErrors;
use Application\Service\AccordionService;
use Application\Service\Factory\AppFunctionsExtensionFactory;
use Application\Service\NavigationViewModelHelper;
use Application\Service\SystemMessage;
use Application\View\Twig\AppFunctionsExtension;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AppFunctionsExtensionFactoryTest extends TestCase
{
    public function testFactoryReturnsAppFunctionsExtension(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'                         => [],
            FormLinkedErrors::class          => $this->createMock(FormLinkedErrors::class),
            TemplateRendererInterface::class => $this->createMock(TemplateRendererInterface::class),
            SystemMessage::class             => $this->createMock(SystemMessage::class),
            AccordionService::class          => $this->createMock(AccordionService::class),
            NavigationViewModelHelper::class => $this->createMock(NavigationViewModelHelper::class),
        });

        $extension = (new AppFunctionsExtensionFactory())($container);

        $this->assertInstanceOf(AppFunctionsExtension::class, $extension);
    }
}
