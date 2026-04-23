<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Service\Factory\ActorReuseDetailsServiceFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ActorReuseDetailsServiceFactoryTest extends TestCase
{
    public function testFactoryReturnsActorReuseDetailsService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            LpaApplicationService::class => $this->createMock(LpaApplicationService::class),
            SessionUtility::class        => $this->createMock(SessionUtility::class),
        });

        $service = (new ActorReuseDetailsServiceFactory())($container);

        $this->assertInstanceOf(ActorReuseDetailsService::class, $service);
    }
}
