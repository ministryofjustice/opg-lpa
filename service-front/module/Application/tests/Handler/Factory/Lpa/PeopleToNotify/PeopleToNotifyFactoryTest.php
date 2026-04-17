<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\PeopleToNotify;

use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyAddHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyEditHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandlerFactory;
use Application\Handler\Factory\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandlerFactory;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyConfirmDeleteHandler;
use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PeopleToNotifyFactoryTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('get')->willReturnCallback(function (string $id) {
            return match ($id) {
                TemplateRendererInterface::class => $this->createMock(TemplateRendererInterface::class),
                FormElementManager::class => $this->createMock(FormElementManager::class),
                LpaApplicationService::class => $this->createMock(LpaApplicationService::class),
                MvcUrlHelper::class => $this->createMock(MvcUrlHelper::class),
                Metadata::class => $this->createMock(Metadata::class),
                ActorReuseDetailsService::class => $this->createMock(ActorReuseDetailsService::class),
            };
        });
    }

    public function testHandlerFactory(): void
    {
        $factory = new PeopleToNotifyHandlerFactory();
        $this->assertInstanceOf(PeopleToNotifyHandler::class, $factory($this->container));
    }

    public function testAddHandlerFactory(): void
    {
        $factory = new PeopleToNotifyAddHandlerFactory();
        $this->assertInstanceOf(PeopleToNotifyAddHandler::class, $factory($this->container));
    }

    public function testEditHandlerFactory(): void
    {
        $factory = new PeopleToNotifyEditHandlerFactory();
        $this->assertInstanceOf(PeopleToNotifyEditHandler::class, $factory($this->container));
    }

    public function testConfirmDeleteHandlerFactory(): void
    {
        $factory = new PeopleToNotifyConfirmDeleteHandlerFactory();
        $this->assertInstanceOf(PeopleToNotifyConfirmDeleteHandler::class, $factory($this->container));
    }

    public function testDeleteHandlerFactory(): void
    {
        $factory = new PeopleToNotifyDeleteHandlerFactory();
        $this->assertInstanceOf(PeopleToNotifyDeleteHandler::class, $factory($this->container));
    }
}
